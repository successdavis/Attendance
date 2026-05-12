<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    // ─── Inertia pages ───────────────────────────────────────────────────────────

    public function daily(Request $request): Response
    {
        $date = $request->query('date', today()->toDateString());

        $logs = AttendanceLog::with('user', 'device')
            ->whereDate('logged_at', $date)
            ->orderBy('logged_at')
            ->get();

        $summary = [
            'date'    => $date,
            'present' => $logs->where('status', 'check_in')->unique('user_id')->count(),
            'total'   => $logs->count(),
        ];

        return Inertia::render('Admin/Reports/Daily', compact('logs', 'summary', 'date'));
    }

    public function range(Request $request): Response
    {
        $from = $request->query('from', today()->startOfWeek()->toDateString());
        $to   = $request->query('to', today()->toDateString());

        $logs = AttendanceLog::with('user', 'device')
            ->whereBetween(\DB::raw('DATE(logged_at)'), [$from, $to])
            ->orderBy('logged_at')
            ->get();

        return Inertia::render('Admin/Reports/Range', compact('logs', 'from', 'to'));
    }

    public function user(Request $request, User $user): Response
    {
        $from = $request->query('from', today()->subDays(29)->toDateString());
        $to   = $request->query('to', today()->toDateString());

        $logs = AttendanceLog::with('device')
            ->where('user_id', $user->id)
            ->whereBetween(\DB::raw('DATE(logged_at)'), [
                $from ?: '2000-01-01',
                $to   ?: today()->toDateString(),
            ])
            ->orderBy('logged_at')
            ->get();

        $summary = [
            'total_days' => (int) AttendanceLog::where('user_id', $user->id)
                ->where('status', 'check_in')
                ->selectRaw('DATE(logged_at) as d')
                ->distinct()
                ->count(),
            'days_this_month' => (int) AttendanceLog::where('user_id', $user->id)
                ->where('status', 'check_in')
                ->whereMonth('logged_at', today()->month)
                ->whereYear('logged_at', today()->year)
                ->selectRaw('DATE(logged_at) as d')
                ->distinct()
                ->count(),
            'last_seen'     => AttendanceLog::where('user_id', $user->id)->latest('logged_at')->value('logged_at'),
            'total_records' => AttendanceLog::where('user_id', $user->id)->count(),
        ];

        $user->load('policy');

        return Inertia::render('Admin/Reports/User', compact('user', 'logs', 'summary', 'from', 'to'));
    }

    // ─── PDF exports ─────────────────────────────────────────────────────────────

    public function exportDaily(Request $request)
    {
        $date = $request->query('date', today()->toDateString());

        $logs = AttendanceLog::with('user', 'device')
            ->whereDate('logged_at', $date)
            ->orderBy('logged_at')
            ->get();

        $summary = [
            'date'    => $date,
            'present' => $logs->where('status', 'check_in')->unique('user_id')->count(),
            'total'   => $logs->count(),
        ];

        $rows = $this->buildUserPairs($logs);

        $pdf = Pdf::loadView('reports.daily-pdf', compact('summary', 'rows'))
            ->setPaper('a4', 'landscape');

        return $pdf->download("attendance-daily-{$date}.pdf");
    }

    public function exportRange(Request $request)
    {
        $from = $request->query('from', today()->startOfWeek()->toDateString());
        $to   = $request->query('to', today()->toDateString());

        $logs = AttendanceLog::with('user', 'device')
            ->whereBetween(\DB::raw('DATE(logged_at)'), [$from, $to])
            ->orderBy('logged_at')
            ->get();

        // Group by date, then by user within each date
        $dayGroups = $logs
            ->groupBy(fn ($l) => $l->logged_at->toDateString())
            ->map(fn ($dayLogs) => [
                'date' => $dayLogs->first()->logged_at->format('D, d M Y'),
                'rows' => $this->buildUserPairs($dayLogs),
            ])
            ->sortKeysDesc()
            ->values()
            ->toArray();

        $summary = [
            'from'    => $from,
            'to'      => $to,
            'total'   => $logs->count(),
            'present' => $logs->where('status', 'check_in')->unique('user_id')->count(),
            'days'    => $logs->groupBy(fn ($l) => $l->logged_at->toDateString())->count(),
        ];

        $pdf = Pdf::loadView('reports.range-pdf', compact('summary', 'dayGroups'))
            ->setPaper('a4', 'landscape');

        return $pdf->download("attendance-range-{$from}-to-{$to}.pdf");
    }

    public function exportUser(Request $request, User $user)
    {
        $from = $request->query('from', today()->subDays(29)->toDateString());
        $to   = $request->query('to', today()->toDateString());

        $logs = AttendanceLog::with('device')
            ->where('user_id', $user->id)
            ->whereBetween(\DB::raw('DATE(logged_at)'), [$from ?: '2000-01-01', $to ?: today()->toDateString()])
            ->orderBy('logged_at')
            ->get();

        // Group by date
        $dayGroups = $logs
            ->groupBy(fn ($l) => $l->logged_at->toDateString())
            ->map(fn ($dayLogs, $date) => [
                'date' => (new \DateTime($date))->format('D, d M Y'),
                'rows' => $this->buildUserPairs($dayLogs, false), // no name column needed
            ])
            ->sortKeysDesc()
            ->values()
            ->toArray();

        $summary = [
            'from'        => $from,
            'to'          => $to,
            'total_days'  => $logs->groupBy(fn ($l) => $l->logged_at->toDateString())->count(),
            'total_scans' => $logs->count(),
        ];

        $pdf = Pdf::loadView('reports.user-pdf', compact('user', 'summary', 'dayGroups'))
            ->setPaper('a4', 'portrait');

        $slug = str_replace(' ', '-', strtolower($user->name));
        return $pdf->download("attendance-{$slug}-{$from}-to-{$to}.pdf");
    }

    // ─── Shared helpers ───────────────────────────────────────────────────────────

    /**
     * Given a collection of logs, group by user and pair check_in / check_out rows.
     * Returns an array of flat row arrays ready for table rendering.
     */
    private function buildUserPairs(Collection $logs, bool $includeNames = true): array
    {
        $byUser = [];

        foreach ($logs as $log) {
            $uid = $log->user_id;
            if (! isset($byUser[$uid])) {
                $byUser[$uid] = ['name' => $log->user?->name ?? "#{$uid}", 'ins' => [], 'outs' => []];
            }
            if ($log->status === 'check_in') $byUser[$uid]['ins'][]  = $log;
            else                             $byUser[$uid]['outs'][] = $log;
        }

        // Sort by name
        uasort($byUser, fn ($a, $b) => strcmp($a['name'], $b['name']));

        $rows = [];
        foreach ($byUser as $bucket) {
            $maxRows = max(count($bucket['ins']), count($bucket['outs']), 1);
            for ($i = 0; $i < $maxRows; $i++) {
                $in  = $bucket['ins'][$i]  ?? null;
                $out = $bucket['outs'][$i] ?? null;
                $ref = $in ?? $out;

                $rows[] = [
                    'name'     => ($includeNames && $i === 0) ? $bucket['name'] : '',
                    'check_in' => $in  ? $in->logged_at->format('h:i A')  : '—',
                    'check_out'=> $out ? $out->logged_at->format('h:i A') : '—',
                    'duration' => $this->calcDuration($in, $out),
                    'method'   => $this->methodLabel($ref),
                    'device'   => $ref?->device?->name ?? '—',
                    'location' => $ref?->location ?? '—',
                    'manual'   => (bool) ($ref?->is_manual),
                ];
            }
        }

        return $rows;
    }

    private function calcDuration($in, $out): string
    {
        if (! $in || ! $out) return '—';
        $mins = (int) $out->logged_at->diffInMinutes($in->logged_at);
        if ($mins <= 0) return '—';
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    }

    private function methodLabel($log): string
    {
        if (! $log) return '—';
        if ($log->is_manual) return 'Manual';
        return match ($log->method) {
            'rfid'        => 'RFID',
            'fingerprint' => 'Fingerprint',
            'face'        => 'Face',
            default       => $log->method ?? '—',
        };
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function daily(Request $request): Response
    {
        $date = $request->query('date', today()->toDateString());

        $logs = AttendanceLog::with('user', 'device')
            ->whereDate('logged_at', $date)
            ->orderBy('logged_at')
            ->get();

        $summary = [
            'date'    => $date,
            'present' => $logs->where('status', 'sign_in')->unique('user_id')->count(),
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
        $from = $request->query('from', today()->startOfMonth()->toDateString());
        $to   = $request->query('to', today()->toDateString());

        $logs = AttendanceLog::with('device')
            ->where('user_id', $user->id)
            ->whereBetween(\DB::raw('DATE(logged_at)'), [$from, $to])
            ->orderBy('logged_at')
            ->get();

        return Inertia::render('Admin/Reports/User', compact('user', 'logs', 'from', 'to'));
    }

    public function export(Request $request)
    {
        $data = $request->validate([
            'format' => 'required|in:csv,excel,pdf',
            'from'   => 'required|date',
            'to'     => 'required|date|after_or_equal:from',
            'user_id' => 'nullable|exists:users,id',
        ]);

        // TODO Phase 7: implement full export via ReportService
        abort(501, 'Export not yet implemented.');
    }
}

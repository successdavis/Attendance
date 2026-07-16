<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Services\AttendanceService;
use App\Services\AttendanceSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService         $attendanceService,
        private readonly AttendanceSettingsService $settings,
    ) {}

    /**
     * Show the kiosk scan page.
     */
    public function showScan(): Response
    {
        $frontendSettings = $this->attendanceService->frontendSettings();

        return Inertia::render('Attendance/Scan', [
            'attendees'              => $this->todayAttendees(),
            'student_count'          => $this->todayCount('student'),
            'staff_count'            => $this->todayCount('staff'),
            'signInEndTime'          => $frontendSettings['signInEnd'],
            'timeRestrictionEnabled' => $frontendSettings['timeRestriction'],
            'timeFormat'             => $this->settings->getString('time_format', '12h'),
        ]);
    }

    /**
     * Process a scan submitted from the web kiosk.
     */
    public function scan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => 'required|string',
            'method'     => 'required|in:rfid,face,fingerprint',
        ]);

        try {
            $result = $this->attendanceService->processScan(
                identifier: $data['identifier'],
                method:     $data['method'],
                device:     null,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }

        $user   = $result['user'];
        $log    = $result['log'];
        $format = $this->settings->getString('time_format', '12h') === '24h' ? 'H:i' : 'h:i A';

        $isCheckIn = $log->status === 'check_in';

        return response()->json([
            'success' => true,
            'status'  => $log->status,
            'message' => $isCheckIn ? 'Checked in successfully.' : 'Checked out successfully.',
            'user'    => [
                'id'        => $user->id,
                'name'      => $user->name,
                'role'      => $user->roles->first()?->name ?? $user->role ?? 'student',
                'photo_url' => $user->profile_photo_url,
            ],
            'log' => [
                'status'    => $log->status,
                'logged_at' => $log->logged_at->format($format),
            ],
        ]);
    }

    /**
     * Kiosk heartbeat. Hitting this authenticated route refreshes the session's
     * last-activity timestamp (so it never idles out) and returns a fresh CSRF
     * token for the long-open scanner page. If the session had lapsed, the
     * remember-me cookie silently re-authenticates before we get here.
     */
    public function keepAlive(): JsonResponse
    {
        return response()->json([
            'ok'   => true,
            'csrf' => csrf_token(),
        ]);
    }

    /**
     * Today's live attendee feed (read-only view).
     */
    public function today(): Response
    {
        $logs = AttendanceLog::with('user')
            ->whereDate('logged_at', now())
            ->latest('logged_at')
            ->get();

        return Inertia::render('Attendance/Today', [
            'logs' => $logs,
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    private function todayAttendees(): array
    {
        $format = $this->settings->getString('time_format', '12h') === '24h' ? 'H:i' : 'h:i A';

        $logs = AttendanceLog::with('user')
            ->whereDate('logged_at', today())
            ->orderBy('logged_at')
            ->get();

        $byUser = [];

        foreach ($logs as $log) {
            $uid = $log->user_id;

            if (! isset($byUser[$uid])) {
                $byUser[$uid] = [
                    'id'          => $uid,
                    'name'        => $log->user->name,
                    'role'        => $log->user->roles->first()?->name ?? $log->user->role ?? 'student',
                    'photo_url'   => $log->user->profile_photo_url,
                    'sign_in_at'  => null,
                    'sign_out_at' => null,
                    'status'      => $log->status,
                ];
            }

            if ($log->status === 'check_in' && $byUser[$uid]['sign_in_at'] === null) {
                $byUser[$uid]['sign_in_at'] = $log->logged_at->format($format);
            }

            if ($log->status === 'check_out') {
                $byUser[$uid]['sign_out_at'] = $log->logged_at->format($format);
            }

            $byUser[$uid]['status'] = $log->status;
        }

        usort($byUser, fn ($a, $b) =>
            ($b['status'] === 'check_in' ? 1 : 0) <=> ($a['status'] === 'check_in' ? 1 : 0)
            ?: strcmp($a['name'], $b['name'])
        );

        return array_values($byUser);
    }

    private function todayCount(string $type): int
    {
        $userIds = AttendanceLog::whereDate('logged_at', today())
            ->where('status', 'check_in')
            ->distinct()
            ->pluck('user_id');

        return \App\Models\User::whereIn('id', $userIds)
            ->when($type === 'student', fn ($q) => $q->where('role', 'student'))
            ->when($type === 'staff',   fn ($q) => $q->where('role', '!=', 'student'))
            ->count();
    }
}

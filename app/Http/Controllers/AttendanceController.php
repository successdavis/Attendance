<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    /**
     * Show the kiosk scan page.
     * Hydrates today's attendee list and relevant settings so the page is
     * useful immediately on load.
     */
    public function showScan(): Response
    {
        $settings = $this->attendanceService->frontendSettings();

        return Inertia::render('Attendance/Scan', [
            'attendees'              => $this->todayAttendees(),
            'student_count'          => $this->todayCount('student'),
            'staff_count'            => $this->todayCount('staff'),
            'signInEndTime'          => $settings['signInEnd'],
            'timeRestrictionEnabled' => $settings['timeRestriction'],
        ]);
    }

    /**
     * Process a scan submitted from the web kiosk.
     *
     * Returns JSON — the Vue page uses fetch(), not Inertia router.post(),
     * so this is a pure JSON API endpoint and must NOT return an Inertia response.
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

        $user = $result['user'];
        $log  = $result['log'];

        // DB stores 'check_in' / 'check_out' — use these values everywhere.
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
                'logged_at' => $log->logged_at->format('H:i'),
            ],
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

    /**
     * Build a per-user attendance summary for today.
     * Each entry contains first sign-in time, latest sign-out time,
     * and the user's current status for the day.
     *
     * @return list<array{id: int, name: string, role: string, photo_url: string, sign_in_at: string|null, sign_out_at: string|null, status: string}>
     */
    private function todayAttendees(): array
    {
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
                $byUser[$uid]['sign_in_at'] = $log->logged_at->format('H:i');
            }

            if ($log->status === 'check_out') {
                // Always keep the latest check-out time
                $byUser[$uid]['sign_out_at'] = $log->logged_at->format('H:i');
            }

            // Track current status (last log wins)
            $byUser[$uid]['status'] = $log->status;
        }

        // Sort: currently-checked-in first, then by name
        usort($byUser, fn ($a, $b) =>
            ($b['status'] === 'check_in' ? 1 : 0) <=> ($a['status'] === 'check_in' ? 1 : 0)
            ?: strcmp($a['name'], $b['name'])
        );

        return array_values($byUser);
    }

    /**
     * Count distinct users who have checked in today, filtered by role.
     * 'staff' = any role that is not 'student'.
     */
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

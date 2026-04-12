<?php

namespace App\Services;

use App\Models\User;
use App\Models\AttendanceLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AttendanceService
{
    protected Carbon $signInEndTime;
    protected bool $timeRestrictionEnabled;
    protected bool $allowMultipleDaily;

    public function __construct()
    {
        $override = DB::table('attendance_overrides')
            ->whereDate('date', today())
            ->first();

        if ($override) {
            $this->timeRestrictionEnabled = (bool)($override->limit_enabled ?? true);
            $this->allowMultipleDaily     = (bool)($override->allow_multiple_daily ?? false);
            $endTime = $override->sign_in_end ?: config('attendance.sign_in_end', '08:30');
        } else {
            $this->timeRestrictionEnabled = (bool)config('attendance.limit_enabled', true);
            $this->allowMultipleDaily     = (bool)config('attendance.allow_multiple_daily', false);
            $endTime = config('attendance.sign_in_end', '08:30');
        }

        $this->signInEndTime = now()->setTimeFromTimeString($endTime);
    }

    public function getSignInEndTime(): ?string
    {
        return $this->timeRestrictionEnabled ? $this->signInEndTime->toDateTimeString() : null;
    }

    public function isTimeRestrictionEnabled(): bool
    {
        return $this->timeRestrictionEnabled;
    }

    public function allowsMultipleDaily(): bool
    {
        return $this->allowMultipleDaily;
    }

    /**
     * Build today's summary.
     * - Multiple daily ON  -> one row per check-in/check-out session.
     * - Multiple daily OFF -> one row per user.
     */
    public function todaySummary(): array
    {
        if ($this->allowMultipleDaily) {
            // Grab all logs for today, grouped by user
            $logs = AttendanceLog::with('user')
                ->whereDate('logged_at', today())
                ->orderBy('user_id')
                ->orderBy('logged_at')
                ->get()
                ->groupBy('user_id');

            $sessions = collect();

            foreach ($logs as $userId => $userLogs) {
                $user = $userLogs->first()->user;

                // iterate chronologically and pair check_in with next check_out
                $currentCheckIn = null;
                foreach ($userLogs as $log) {
                    if ($log->status === 'check_in') {
                        $currentCheckIn = $log;
                    } elseif ($log->status === 'check_out' && $currentCheckIn) {
                        $sessions->push([
                            'id'          => $user->id,
                            'name'        => $user->name,
                            'role'        => $user->role,
                            'sign_in_at'  => $currentCheckIn->logged_at->toDateTimeString(),
                            'sign_out_at' => $log->logged_at->toDateTimeString(),
                        ]);
                        $currentCheckIn = null;
                    }
                }

                // still checked in without a checkout
                if ($currentCheckIn) {
                    $sessions->push([
                        'id'          => $user->id,
                        'name'        => $user->name,
                        'role'        => $user->role,
                        'sign_in_at'  => $currentCheckIn->logged_at->toDateTimeString(),
                        'sign_out_at' => null,
                    ]);
                }
            }

            return [
                'attendees'     => $sessions->sortByDesc('sign_in_at')->values(),
                'student_count' => $sessions->where('role', 'student')->count(),
                'staff_count'   => $sessions->where('role', 'staff')->count(),
            ];
        }

        // ❄️ single record per user (original behaviour)
        $attendees = User::whereHas('attendanceLogs', function ($q) {
                $q->whereDate('logged_at', today())
                  ->where('status', 'check_in');
            })
            ->with(['todayCheckIn', 'todayCheckOut'])
            ->orderByDesc('id')
            ->get();

        return [
            'attendees' => $attendees->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'role'        => $p->role,
                'sign_in_at'  => optional($p->todayCheckIn)->logged_at?->toDateTimeString(),
                'sign_out_at' => optional($p->todayCheckOut)->logged_at?->toDateTimeString(),
            ]),
            'student_count' => $attendees->where('role', 'student')->count(),
            'staff_count'   => $attendees->where('role', 'staff')->count(),
        ];
    }

    /**
     * Process a scan from RFID/face/fingerprint.
     */
    public function processScan(string $identifier, string $method): array
    {
        $user = match ($method) {
            'rfid'        => User::where('rfid_uid', $identifier)->first(),
            'face'        => User::where('face_template_id', $identifier)->first(),
            'fingerprint' => User::where('fingerprint_template_id', $identifier)->first(),
        };

        if (! $user) {
            throw ValidationException::withMessages(['identifier' => 'User not found for this credential.']);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages(['identifier' => 'This user is inactive or suspended.']);
        }

        $latestLog = $user->attendanceLogs()->latest()->first();

        // Rate-limit duplicate scans within 60 seconds
        if ($latestLog && $latestLog->logged_at->diffInSeconds(now()) < 60) {
            throw ValidationException::withMessages(['identifier' => 'Duplicate scan detected. Please wait a moment.']);
        }

        $nextStatus = $latestLog && $latestLog->status === 'check_in'
            ? 'check_out'
            : 'check_in';

        // Prevent multiple same-day check-ins if disabled
        if (! $this->allowMultipleDaily && $nextStatus === 'check_in') {
            $hasCheckedInToday = $user->attendanceLogs()
                ->whereDate('logged_at', today())
                ->where('status', 'check_in')
                ->exists();

            if ($hasCheckedInToday) {
                throw ValidationException::withMessages([
                    'identifier' => 'User has already signed in today.',
                ]);
            }
        }

        // Optional time-window logic
        if ($this->timeRestrictionEnabled) {
            if (now()->greaterThan($this->signInEndTime)) {
                if ($nextStatus === 'check_in') {
                    throw ValidationException::withMessages([
                        'identifier' => 'Sign-in window has closed for today.',
                    ]);
                }
            } else {
                if ($nextStatus === 'check_out') {
                    throw ValidationException::withMessages([
                        'identifier' => 'Checkout is not allowed until sign-in ends.',
                    ]);
                }
            }
        }

        $log = DB::transaction(fn () =>
            AttendanceLog::create([
                'user_id'    => $user->id,
                'method'     => $method,
                'identifier' => $identifier,
                'status'     => $nextStatus,
                'logged_at'  => now(),
            ])
        );

        return [
            'user' => [
                'id'        => $user->id,
                'name'      => $user->name,
                'role'      => $user->role,
                'photo_url' => $user->profilePhotoUrl(),
            ],
            'log' => [
                'status'    => $log->status,
                'logged_at' => $log->logged_at->toDateTimeString(),
            ],
        ];
    }
}

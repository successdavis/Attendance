<?php

namespace App\Services;

use App\Models\AttendanceDevice;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Services\Attendance\Contracts\AttendanceMethodInterface;
use App\Services\Attendance\FaceProvider;
use App\Services\Attendance\FingerprintProvider;
use App\Services\Attendance\RfidProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * AttendanceService
 *
 * Central attendance business logic. All scan processing (web UI or device API)
 * routes through this service. Provider classes handle credential resolution so
 * this service stays method-agnostic.
 */
class AttendanceService
{
    /** @var array<string, AttendanceMethodInterface> */
    private array $providers;

    public function __construct(
        private readonly AttendanceSettingsService $settings
    ) {
        $this->providers = [
            'rfid'        => new RfidProvider(),
            'fingerprint' => new FingerprintProvider(),
            'face'        => new FaceProvider(),
        ];
    }

    /**
     * Process an attendance scan.
     *
     * @param string               $identifier  Raw credential value from the device
     * @param string               $method      rfid | fingerprint | face
     * @param AttendanceDevice|null $device     Physical device that submitted the scan (null for web UI)
     * @return array{user: User, log: AttendanceLog}
     *
     * @throws ValidationException  On any business rule violation (user not found, inactive, etc.)
     */
    public function processScan(
        string $identifier,
        string $method,
        ?AttendanceDevice $device = null
    ): array {
        // 1. Ensure this method is enabled in settings
        $allowedMethods = $this->settings->getJson('allowed_methods', ['rfid']);

        if (! in_array($method, $allowedMethods, true)) {
            throw ValidationException::withMessages([
                'method' => "Attendance method [{$method}] is not enabled.",
            ]);
        }

        // 2. Resolve provider and find user
        $provider = $this->resolveProvider($method);
        $user     = $provider->resolveUser($identifier);

        if (! $user) {
            throw ValidationException::withMessages([
                'identifier' => 'User not found for this credential.',
            ]);
        }

        // 3. Check user status
        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'identifier' => 'This user is inactive or suspended.',
            ]);
        }

        // 4. Enforce program expiry for students
        if (
            $this->settings->getBool('program_expiry_enforcement', true)
            && $user->role === 'student'
            && $user->hasExpiredProgram()
        ) {
            throw ValidationException::withMessages([
                'identifier' => 'Student program enrollment has expired.',
            ]);
        }

        // 5. Weekend restriction
        if (! $this->settings->getBool('weekend_attendance_allowed', false)) {
            if (now()->isWeekend()) {
                throw ValidationException::withMessages([
                    'identifier' => 'Attendance is not allowed on weekends.',
                ]);
            }
        }

        // 6. Duplicate scan (debounce) — configurable, default 60s
        $latestLog = $user->attendanceLogs()->latest('logged_at')->first();

        $debounce = $this->settings->getInt('scan_debounce_seconds', 60);

        if ($latestLog && $latestLog->logged_at->diffInSeconds(now()) < $debounce) {
            throw ValidationException::withMessages([
                'identifier' => 'Duplicate scan detected. Please wait a moment.',
            ]);
        }

        // 7. Anti-passback: ensure alternating check-in/check-out (optional)
        if ($this->settings->getBool('anti_passback_enabled', false) && $latestLog) {
            // If they already checked in, their next scan must be check-out (handled by status toggle)
            // Anti-passback specifically means: you cannot check-in without checking out first from
            // a different entry point. Since we don't track entry points yet, this is a no-op
            // placeholder for the rule to be wired in Phase 5 when device locations are managed.
        }

        // 8. Time window restriction (optional)
        $nextStatus = ($latestLog && $latestLog->status === 'check_in') ? 'check_out' : 'check_in';

        if ($this->settings->getBool('time_restriction_enabled', false)) {
            $this->enforceTimeWindow($nextStatus);
        }

        // 9. Create the attendance log inside a transaction
        $log = DB::transaction(fn () => AttendanceLog::create([
            'user_id'   => $user->id,
            'method'    => $method,
            'device_id' => $device?->id,
            'location'  => $device?->location,
            'identifier' => $identifier,
            'status'    => $nextStatus,
            'logged_at' => now(),
        ]));

        return compact('user', 'log');
    }

    /**
     * Determine whether a user's latest log means they're currently checked in.
     */
    public function isCheckedIn(User $user): bool
    {
        $latest = $user->attendanceLogs()->latest('logged_at')->first();

        return $latest && $latest->status === 'check_in';
    }

    /**
     * Get all settings needed by the frontend scan page.
     */
    public function frontendSettings(): array
    {
        return [
            'allowedMethods'     => $this->settings->getJson('allowed_methods', ['rfid']),
            'debounceSeconds'    => $this->settings->getInt('scan_debounce_seconds', 60),
            'timeRestriction'    => $this->settings->getBool('time_restriction_enabled'),
            'signInStart'        => $this->settings->getString('sign_in_start_time', '07:00'),
            'signInEnd'          => $this->settings->getString('sign_in_end_time', '10:00'),
            'signOutStart'       => $this->settings->getString('sign_out_start_time', '16:00'),
            'signOutEnd'         => $this->settings->getString('sign_out_end_time', '20:00'),
        ];
    }

    // ─── Internals ───────────────────────────────────────────────────────────────

    private function resolveProvider(string $method): AttendanceMethodInterface
    {
        $provider = $this->providers[$method] ?? null;

        if (! $provider) {
            throw ValidationException::withMessages([
                'method' => "Unknown attendance method [{$method}].",
            ]);
        }

        return $provider;
    }

    private function enforceTimeWindow(string $nextStatus): void
    {
        $now = now();

        if ($nextStatus === 'check_in') {
            $start = $this->settings->getTime('sign_in_start_time', '07:00');
            $end   = $this->settings->getTime('sign_in_end_time', '10:00');
            $grace = $this->settings->getInt('grace_period_minutes', 15);

            // Add grace period on top of the end time
            $deadline = $end?->copy()->addMinutes($grace);

            if ($start && $now->lt($start)) {
                throw ValidationException::withMessages([
                    'identifier' => "Sign-in is not open yet. Opens at {$start->format('H:i')}.",
                ]);
            }

            if ($deadline && $now->gt($deadline)) {
                throw ValidationException::withMessages([
                    'identifier' => "Sign-in window has closed.",
                ]);
            }
        }

        if ($nextStatus === 'check_out') {
            $start = $this->settings->getTime('sign_out_start_time', '16:00');
            $end   = $this->settings->getTime('sign_out_end_time', '20:00');

            if ($start && $now->lt($start)) {
                throw ValidationException::withMessages([
                    'identifier' => "Sign-out is not open yet. Opens at {$start->format('H:i')}.",
                ]);
            }

            if ($end && $now->gt($end)) {
                throw ValidationException::withMessages([
                    'identifier' => "Sign-out window has closed.",
                ]);
            }
        }
    }
}

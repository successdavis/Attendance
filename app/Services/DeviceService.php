<?php

namespace App\Services;

use App\Models\AttendanceDevice;
use Illuminate\Support\Str;

class DeviceService
{
    /**
     * Register a new attendance device.
     *
     * Generates a plain API token (shown once to the admin) and stores only
     * the SHA-256 hash so the token cannot be recovered if the database is compromised.
     *
     * @return array{device: AttendanceDevice, plainToken: string}
     */
    public function register(array $data, int $adminId): array
    {
        $plainToken = $this->generateToken();

        $device = AttendanceDevice::create(array_merge($data, [
            'api_token_hash' => hash('sha256', $plainToken),
            'registered_by'  => $adminId,
            'status'         => 'active',
        ]));

        activity()
            ->causedBy(auth()->user())
            ->performedOn($device)
            ->log('Device registered');

        return ['device' => $device, 'plainToken' => $plainToken];
    }

    /**
     * Rotate the API token for an existing device.
     * The old token is immediately invalidated (hash replaced in DB).
     *
     * @return string  The new plain token (show once, then discard).
     */
    public function rotateToken(AttendanceDevice $device, int $adminId): string
    {
        $plainToken = $this->generateToken();

        $device->update(['api_token_hash' => hash('sha256', $plainToken)]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($device)
            ->log('Device token rotated');

        return $plainToken;
    }

    /**
     * Validate a plain bearer token against the database.
     * Used by AuthenticateDevice middleware.
     */
    public function validateToken(string $plainToken): ?AttendanceDevice
    {
        return AttendanceDevice::where('api_token_hash', hash('sha256', $plainToken))
            ->where('status', 'active')
            ->first();
    }

    /**
     * Mark a device as inactive (soft-disable without deleting).
     */
    public function deactivate(AttendanceDevice $device, int $adminId): void
    {
        $device->update(['status' => 'inactive']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($device)
            ->log('Device deactivated');
    }

    // ─── Internal ────────────────────────────────────────────────────────────────

    private function generateToken(): string
    {
        // 40 random bytes = 80 hex chars — sufficient entropy for device auth
        return bin2hex(random_bytes(40));
    }
}

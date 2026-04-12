<?php

namespace App\Services\Attendance\Contracts;

use App\Models\User;

/**
 * Contract that every attendance authentication provider must implement.
 *
 * Add new methods (QR code, PIN, NFC, mobile app) by implementing this
 * interface and registering the provider in AttendanceService — no other
 * files need to change.
 */
interface AttendanceMethodInterface
{
    /**
     * Resolve the user who owns the given credential identifier.
     *
     * @param string $identifier  Raw credential value (card UID, template hash, etc.)
     * @return User|null          The matching active user, or null if not found.
     */
    public function resolveUser(string $identifier): ?User;

    /**
     * Return the method name that this provider handles (e.g. 'rfid').
     * Must match the values stored in attendance_logs.method.
     */
    public function getMethod(): string;
}

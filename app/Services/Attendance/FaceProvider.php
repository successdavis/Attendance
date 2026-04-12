<?php

namespace App\Services\Attendance;

use App\Models\User;
use App\Models\UserCredential;
use App\Services\Attendance\Contracts\AttendanceMethodInterface;

class FaceProvider implements AttendanceMethodInterface
{
    public function resolveUser(string $identifier): ?User
    {
        $credential = UserCredential::with('user')
            ->where('type', 'face')
            ->where('value', $identifier)
            ->where('is_active', true)
            ->first();

        return $credential?->user;
    }

    public function getMethod(): string
    {
        return 'face';
    }
}

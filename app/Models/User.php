<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Fortify\TwoFactorAuthenticatable;


class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'rfid_uid',
        'face_template_id',
        'fingerprint_template_id',
        'status',
        'left_at',
        'profile_photo_path',
    ];

    protected $casts = [
        'left_at' => 'datetime',
    ];

    /** Relationships */
    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function latestLog()
    {
        return $this->hasOne(AttendanceLog::class)->latestOfMany();
    }

    public function todayCheckIn()
    {
        return $this->hasOne(AttendanceLog::class)
            ->whereDate('logged_at', today())
            ->where('status', 'check_in')
            ->orderBy('logged_at'); // first check-in of the day
    }

    public function todayCheckOut()
    {
        return $this->hasOne(AttendanceLog::class)
            ->whereDate('logged_at', today())
            ->where('status', 'check_out')
            ->latestOfMany('logged_at'); // latest checkout of the day
    }

    /** Check if the user is currently active */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** URL for profile photo (with fallback) */
    public function profilePhotoUrl(): string
    {
        return $this->profile_photo_path
            ? asset('storage/' . $this->profile_photo_path)
            : asset('images/default-avatar.png');
    }
}

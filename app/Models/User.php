<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'left_at',
        'profile_photo_path',
        'external_student_id',
        'external_synced_at',
        'program_expires_at',
        'branch',
        'notes',
        'policy_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = ['profile_photo_url'];

    protected $casts = [
        'left_at'                     => 'datetime',
        'external_synced_at'          => 'datetime',
        'program_expires_at'          => 'datetime',
        'email_verified_at'           => 'datetime',
        'two_factor_confirmed_at'     => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(UserCredential::class);
    }

    public function activeCredentials(): HasMany
    {
        return $this->hasMany(UserCredential::class)->where('is_active', true);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(AttendancePolicy::class, 'policy_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(UserStatusLog::class);
    }

    // ─── Business logic helpers ──────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasExpiredProgram(): bool
    {
        return $this->program_expires_at !== null && $this->program_expires_at->isPast();
    }

    /**
     * Appended accessor — always available as $user->profile_photo_url in JSON/Inertia props.
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_photo_path
            ? asset('storage/' . $this->profile_photo_path)
            : '';
    }
}

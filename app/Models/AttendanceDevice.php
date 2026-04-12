<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceDevice extends Model
{
    protected $fillable = [
        'device_uid',
        'name',
        'type',
        'location',
        'branch',
        'ip_address',
        'api_token_hash',
        'status',
        'last_seen_at',
        'last_ip',
        'firmware_version',
        'config_json',
        'registered_by',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'config_json'  => 'array',
    ];

    // Never expose the token hash in API responses
    protected $hidden = ['api_token_hash'];

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(UserCredential::class, 'device_id');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'device_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

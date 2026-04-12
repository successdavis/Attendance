<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'method',
        'device_id',
        'location',
        'identifier',
        'status',
        'logged_at',
        'override_reason',
        'is_manual',
        'created_by',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'is_manual' => 'boolean',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function correction(): HasOne
    {
        return $this->hasOne(AttendanceCorrection::class);
    }
}

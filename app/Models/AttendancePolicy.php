<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePolicy extends Model
{
    protected $fillable = [
        'name',
        'description',
        'sign_in_start',
        'sign_in_end',
        'sign_out_start',
        'sign_out_end',
        'grace_period_minutes',
        'late_threshold_minutes',
        'allow_multiple_daily',
        'weekend_allowed',
        'time_restriction_enabled',
        'is_default',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'allow_multiple_daily'     => 'boolean',
        'weekend_allowed'          => 'boolean',
        'time_restriction_enabled' => 'boolean',
        'is_default'               => 'boolean',
        'is_active'                => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'policy_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

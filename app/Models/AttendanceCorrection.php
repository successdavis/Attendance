<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attendance_log_id',
        'user_id',
        'action',
        'original_data',
        'corrected_data',
        'reason',
        'corrected_by',
        'corrected_at',
    ];

    protected $casts = [
        'original_data'  => 'array',
        'corrected_data' => 'array',
        'corrected_at'   => 'datetime',
    ];

    public function attendanceLog(): BelongsTo
    {
        return $this->belongsTo(AttendanceLog::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }
}

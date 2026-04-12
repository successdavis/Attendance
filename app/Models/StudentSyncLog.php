<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSyncLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'triggered_by',
        'triggered_by_user',
        'started_at',
        'finished_at',
        'status',
        'total_fetched',
        'total_created',
        'total_updated',
        'total_deactivated',
        'total_skipped',
        'error_message',
        'response_snapshot',
    ];

    protected $casts = [
        'started_at'        => 'datetime',
        'finished_at'       => 'datetime',
        'response_snapshot' => 'array',
    ];

    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user');
    }

    public function getDurationAttribute(): ?string
    {
        if (! $this->finished_at) {
            return null;
        }

        return $this->started_at->diffForHumans($this->finished_at, true);
    }
}

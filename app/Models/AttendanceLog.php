<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'method',
        'identifier',
        'status',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    /** Relationships */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

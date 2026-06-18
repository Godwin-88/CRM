<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditAnomaly extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'event_type',
        'description',
        'metadata',
        'detected_at',
        'acknowledged_at',
        'acknowledged_by',
        'acknowledged_note',
        'email_sent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'detected_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'email_sent' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}

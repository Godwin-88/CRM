<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DripEnrolment extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'drip_sequence_id',
        'contact_id',
        'current_step_data',
        'status',
        'enroled_at',
        'completed_at',
        'retry_count',
        'error_message',
    ];

    protected $casts = [
        'current_step_data' => 'array',
        'enroled_at' => 'datetime',
        'completed_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(DripSequence::class, 'drip_sequence_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isExited(): bool
    {
        return $this->status === 'exited';
    }
}

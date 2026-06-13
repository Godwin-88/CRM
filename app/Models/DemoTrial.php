<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoTrial extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'deal_id',
        'type',
        'scheduled_date',
        'start_date',
        'end_date',
        'scope_notes',
        'assigned_to',
        'status',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function isNoShow(): bool
    {
        return $this->status === 'no_show';
    }
}

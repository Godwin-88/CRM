<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationJob extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'deal_automation_id',
        'deal_id',
        'automation_action_id',
        'status',
        'scheduled_at',
        'processed_at',
        'retry_count',
        'error_message',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'processed_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(DealAutomation::class, 'deal_automation_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(AutomationAction::class, 'automation_action_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
}

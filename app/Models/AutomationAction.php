<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationAction extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'deal_automation_id',
        'action_type',
        'delay_type',
        'delay_days',
        'assigned_to',
        'email_to',
        'webhook_url',
        'position',
    ];

    protected $casts = [
        'delay_days' => 'integer',
        'position' => 'integer',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(DealAutomation::class, 'deal_automation_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getDelayInSeconds(): int
    {
        return match ($this->delay_type) {
            'immediate' => 0,
            'one_hour' => 3600,
            'one_day' => 86400,
            'n_business_days' => ($this->delay_days ?? 1) * 86400,
            default => 0,
        };
    }
}

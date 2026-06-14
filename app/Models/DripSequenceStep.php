<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DripSequenceStep extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'drip_sequence_id',
        'position',
        'action_type',
        'config',
        'delay_days',
        'sort_order',
        'email_template_id',
        'sms_content',
        'in_app_title',
        'in_app_content',
        'activity_type',
        'field_key',
        'field_value',
        'segment_id',
        'agent_id',
        'delay_type',
        'delay_value',
        'exit_conditions',
    ];

    protected $casts = [
        'position' => 'integer',
        'config' => 'array',
        'delay_days' => 'integer',
        'sort_order' => 'integer',
        'delay_value' => 'integer',
        'exit_conditions' => 'array',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(DripSequence::class, 'drip_sequence_id');
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(CampaignTemplate::class, 'email_template_id');
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function getDelayInSeconds(): int
    {
        return match ($this->delay_type) {
            'immediate' => 0,
            'n_hours' => ($this->delay_value ?? 1) * 3600,
            'n_days' => ($this->delay_value ?? 1) * 86400,
            default => 0,
        };
    }
}

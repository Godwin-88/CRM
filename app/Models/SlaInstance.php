<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SlaInstance extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ticket_id',
        'target_type',
        'target_id',
        'entity_type',
        'sla_definition_id',
        'assigned_at',
        'first_response_deadline',
        'resolution_deadline',
        'acknowledgement_deadline',
        'review_deadline',
        'next_action_deadline',
        'completion_deadline',
        'triage_deadline',
        'investigation_update_deadline',
        'resolution_proposal_deadline',
        'closure_signoff_deadline',
        'first_response_met_at',
        'resolution_met_at',
        'acknowledgement_met_at',
        'review_met_at',
        'next_action_met_at',
        'completion_met_at',
        'triage_met_at',
        'investigation_update_met_at',
        'resolution_proposal_met_at',
        'closure_signoff_met_at',
        'paused_at',
        'resumed_at',
        'pause_reason',
        'resume_reason',
        'first_response_breached',
        'resolution_breached',
        'acknowledgement_breached',
        'review_breached',
        'next_action_breached',
        'completion_breached',
        'triage_breached',
        'investigation_update_breached',
        'resolution_proposal_breached',
        'closure_signoff_breached',
        'milestone_definitions',
        'milestone_states',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'first_response_deadline' => 'datetime',
        'resolution_deadline' => 'datetime',
        'acknowledgement_deadline' => 'datetime',
        'review_deadline' => 'datetime',
        'next_action_deadline' => 'datetime',
        'completion_deadline' => 'datetime',
        'triage_deadline' => 'datetime',
        'investigation_update_deadline' => 'datetime',
        'resolution_proposal_deadline' => 'datetime',
        'closure_signoff_deadline' => 'datetime',
        'first_response_met_at' => 'datetime',
        'resolution_met_at' => 'datetime',
        'acknowledgement_met_at' => 'datetime',
        'review_met_at' => 'datetime',
        'next_action_met_at' => 'datetime',
        'completion_met_at' => 'datetime',
        'triage_met_at' => 'datetime',
        'investigation_update_met_at' => 'datetime',
        'resolution_proposal_met_at' => 'datetime',
        'closure_signoff_met_at' => 'datetime',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'first_response_breached' => 'boolean',
        'resolution_breached' => 'boolean',
        'acknowledgement_breached' => 'boolean',
        'review_breached' => 'boolean',
        'next_action_breached' => 'boolean',
        'completion_breached' => 'boolean',
        'triage_breached' => 'boolean',
        'investigation_update_breached' => 'boolean',
        'resolution_proposal_breached' => 'boolean',
        'closure_signoff_breached' => 'boolean',
        'milestone_definitions' => 'array',
        'milestone_states' => 'array',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    public function pause(string $reason): void
    {
        $this->paused_at ??= now();
        $this->pause_reason = $reason;
        $this->save();
    }

    public function resume(string $reason): void
    {
        if (! $this->paused_at) {
            return;
        }

        $pausedDuration = now()->diffInSeconds($this->paused_at);

        $this->resumed_at = now();
        $this->resume_reason = $reason;
        $this->save();

        $this->extendDeadlines($pausedDuration);
    }

    protected function extendDeadlines(int $seconds): void
    {
        $deadlineColumns = [
            'first_response_deadline',
            'resolution_deadline',
            'acknowledgement_deadline',
            'review_deadline',
            'next_action_deadline',
            'completion_deadline',
            'triage_deadline',
            'investigation_update_deadline',
            'resolution_proposal_deadline',
            'closure_signoff_deadline',
        ];

        foreach ($deadlineColumns as $column) {
            if ($this->{$column}) {
                $this->{$column} = $this->{$column}->addSeconds($seconds);
            }
        }

        $this->paused_at = null;
        $this->save();
    }

    public function slaDefinition(): BelongsTo
    {
        return $this->belongsTo(SlaDefinition::class);
    }
}

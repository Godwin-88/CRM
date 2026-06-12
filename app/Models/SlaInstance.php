<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaInstance extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ticket_id',
        'sla_definition_id',
        'assigned_at',
        'first_response_deadline',
        'resolution_deadline',
        'first_response_met_at',
        'resolution_met_at',
        'paused_at',
        'resumed_at',
        'first_response_breached',
        'resolution_breached',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'first_response_deadline' => 'datetime',
        'resolution_deadline' => 'datetime',
        'first_response_met_at' => 'datetime',
        'resolution_met_at' => 'datetime',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'first_response_breached' => 'boolean',
        'resolution_breached' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function slaDefinition(): BelongsTo
    {
        return $this->belongsTo(SlaDefinition::class);
    }
}

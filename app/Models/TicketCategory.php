<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'default_priority',
        'default_team_id',
        'sla_policy_id',
        'is_agent_only',
        'is_active',
    ];

    protected $casts = [
        'is_agent_only' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaDefinition::class, 'sla_policy_id');
    }

    public function defaultTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'default_team_id');
    }

    public function form(): HasOne
    {
        return $this->hasOne(TicketForm::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAgentVisible($query)
    {
        return $query->where('is_agent_only', false);
    }
}

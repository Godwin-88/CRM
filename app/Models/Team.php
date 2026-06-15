<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'description',
        'is_archived',
        'team_lead_id',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }
}
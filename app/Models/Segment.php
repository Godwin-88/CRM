<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Segment extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'description',
        'type',
        'goal',
        'status',
        'criteria',
        'join_operator',
        'campaign_id',
        'tags',
        'channel_eligibility',
        'contact_count',
        'contact_count_cached_at',
        'last_evaluated_at',
        'created_by',
    ];

    protected $casts = [
        'criteria' => 'array',
        'tags' => 'array',
        'channel_eligibility' => 'array',
        'contact_count' => 'integer',
        'contact_count_cached_at' => 'datetime',
        'last_evaluated_at' => 'datetime',
    ];

    const TYPES = ['demographic', 'psychographic', 'behavioral', 'geographic', 'firmographic', 'technographic'];
    const GOALS = ['acquisition', 'retention', 'reactivation', 'upsell', 'cross_sell', 'loyalty', 'awareness', 'win_back'];
    const STATUSES = ['draft', 'active', 'paused', 'archived'];

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_segments');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
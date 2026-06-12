<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuidedJourney extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'steps',
        'is_published',
        'notify_agent_on_completion',
        'created_by',
    ];

    protected $casts = [
        'steps' => 'array',
        'is_published' => 'boolean',
        'notify_agent_on_completion' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(JourneyCompletion::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourneyCompletion extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'journey_id',
        'contact_id',
        'inputs',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'inputs' => 'array',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function journey(): BelongsTo
    {
        return $this->belongsTo(GuidedJourney::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}

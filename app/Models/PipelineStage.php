<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineStage extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'pipeline_id',
        'name',
        'probability',
        'description',
        'position',
    ];

    protected $casts = [
        'probability' => 'integer',
        'position' => 'integer',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'stage', 'name');
    }

    public function automations(): HasMany
    {
        return $this->hasMany(DealAutomation::class);
    }
}
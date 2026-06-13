<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignABTest extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'campaign_id',
        'test_type',
        'winner_criterion',
        'test_percentage',
        'duration_hours',
        'started_at',
        'concluded_at',
        'status',
        'winning_variant',
        'variant_a_template_id',
        'variant_b_template_id',
        'subject_line_a',
        'subject_line_b',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'concluded_at' => 'datetime',
        'test_percentage' => 'integer',
        'duration_hours' => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function variantATemplate(): BelongsTo
    {
        return $this->belongsTo(CampaignTemplate::class, 'variant_a_template_id');
    }

    public function variantBTemplate(): BelongsTo
    {
        return $this->belongsTo(CampaignTemplate::class, 'variant_b_template_id');
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isConcluded(): bool
    {
        return in_array($this->status, ['concluded', 'inconclusive']);
    }
}

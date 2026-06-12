<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignTemplate extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'subject',
        'html_content',
        'raw_html',
        'status',
        'created_by',
        'reviewed_by',
        'reviewed_at',
        'version',
        'is_active',
        'blocks',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'version' => 'integer',
        'is_active' => 'boolean',
        'blocks' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function campaignSteps(): HasMany
    {
        return $this->hasMany(CampaignStep::class, 'email_template_id');
    }

    public function dripSequenceSteps(): HasMany
    {
        return $this->hasMany(DripSequenceStep::class, 'email_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
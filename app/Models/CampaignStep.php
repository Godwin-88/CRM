<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignStep extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'campaign_id',
        'position',
        'channel',
        'email_template_id',
        'sms_content',
        'push_title',
        'push_content',
        'in_app_title',
        'in_app_content',
        'whatsapp_content',
        'facebook_content',
        'instagram_content',
        'tiktok_content',
        'linkedin_content',
        'social_image_url',
        'delay_type',
        'delay_value',
        'status',
    ];

    protected $casts = [
        'position' => 'integer',
        'delay_value' => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(CampaignTemplate::class, 'email_template_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function getDelayInSeconds(): int
    {
        return match ($this->delay_type) {
            'immediately', 'immediate' => 0,
            'n_hours' => ($this->delay_value ?? 1) * 3600,
            'n_days' => ($this->delay_value ?? 1) * 86400,
            default => 0,
        };
    }
}

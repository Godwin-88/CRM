<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'campaign_id',
        'channel',
        'content',
        'media_url',
        'scheduled_at',
        'published_at',
        'likes',
        'comments',
        'shares',
        'impressions',
        'status',
        'error_message',
        'external_id',
        'channel_specific_data',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'likes' => 'integer',
        'comments' => 'integer',
        'shares' => 'integer',
        'impressions' => 'integer',
        'channel_specific_data' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function maxCharacterLimit(): int
    {
        return match ($this->channel) {
            'linkedin' => 3000,
            'x' => 280,
            'facebook' => 63206,
            default => 280,
        };
    }
}
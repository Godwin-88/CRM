<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'status',
        'segment_id',
        'created_by',
        'scheduled_at',
        'started_at',
        'completed_at',
        'throttle_emails_per_hour',
        'throttle_sms_per_hour',
        'optimize_send_time',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'tags',
        'budget',
        'spent',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'throttle_emails_per_hour' => 'integer',
        'throttle_sms_per_hour' => 'integer',
        'optimize_send_time' => 'boolean',
        'tags' => 'array',
        'budget' => 'decimal:2',
        'spent' => 'decimal:2',
    ];

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(CampaignStep::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function abTest(): BelongsTo
    {
        return $this->belongsTo(CampaignABTest::class);
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['sending', 'scheduled']);
    }

    public function canBeScheduled(): bool
    {
        return $this->isDraft() && $this->segment_id && $this->steps()->exists() && $this->steps()->whereNull('email_template_id')->doesntExist();
    }
}

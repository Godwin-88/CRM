<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Interaction extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'contact_id',
        'account_id',
        'deal_id',
        'type',
        'channel_id',
        'direction',
        'subject',
        'body',
        'duration_seconds',
        'outcome',
        'agent_id',
        'external_message_id',
        'parent_interaction_id',
        'metadata',
        'is_reviewed',
        'is_locked',
        'locked_by',
        'locked_at',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(InteractionChannel::class, 'channel_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InteractionAttachment::class);
    }

    public function callRecording(): HasOne
    {
        return $this->hasOne(CallRecording::class);
    }

    public function chatSession(): HasOne
    {
        return $this->hasOne(ChatSession::class);
    }

    public function scopeUnmatched($query)
    {
        return $query->whereNull('contact_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'call' => 'Call',
            'email' => 'Email',
            'chat' => 'Live Chat',
            'sms' => 'SMS',
            'whatsapp' => 'WhatsApp',
            'facebook' => 'Facebook',
            'linkedin' => 'LinkedIn',
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            'in_person' => 'In-Person',
            'field_visit' => 'Field Visit',
            'kiosk' => 'Kiosk',
        ];
        return $labels[$this->attributes['type']] ?? ($this->channel?->display_name ?? $this->attributes['type'] ?? 'Unknown');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function parentInteraction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class, 'parent_interaction_id');
    }

    public function childInteractions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'parent_interaction_id');
    }
}

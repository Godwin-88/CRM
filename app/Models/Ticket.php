<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Ticket extends Model implements HasMedia
{
    use HasFactory, HasUlids, SoftDeletes, LogsActivity, InteractsWithMedia;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    protected $fillable = [
        'subject',
        'description',
        'contact_id',
        'account_id',
        'priority',
        'status',
        'category_id',
        'assigned_to',
        'sla_breached_at',
        'resolved_at',
        'closed_at',
        'merged_into_ticket_id',
        'merged_at',
        'split_from_ticket_id',
        'escalation_reason',
        'is_agent_created',
    ];

    protected $casts = [
        'sla_breached_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'merged_at' => 'datetime',
        'is_agent_created' => 'boolean',
    ];

    protected $attributes = [
        'priority' => 'medium',
        'status' => 'open',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (!$ticket->priority) {
                $ticket->priority = 'medium';
            }
            if (!$ticket->status) {
                $ticket->status = 'open';
            }
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function slaInstance(): HasOne
    {
        return $this->hasOne(SlaInstance::class);
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(TicketInternalNote::class);
    }

    public function relatedTickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_relations', 'ticket_id', 'related_ticket_id')
            ->withTimestamps();
    }

    public function mergedTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_ticket_id');
    }

    public function mergedFrom(): HasMany
    {
        return $this->hasMany(self::class, 'merged_into_ticket_id');
    }

    public function splitFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'split_from_ticket_id');
    }

    public function splits(): HasMany
    {
        return $this->hasMany(self::class, 'split_from_ticket_id');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(TicketRating::class);
    }

    public function formResponse(): HasOne
    {
        return $this->hasOne(TicketFormResponse::class);
    }

    public function linkedArticles(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeBaseArticle::class, 'article_ticket_links')
            ->withTimestamps();
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function canBeReopened(): bool
    {
        if (!$this->resolved_at) {
            return false;
        }

        return $this->resolved_at->isAfter(now()->subDays(7));
    }

    public function isMerged(): bool
    {
        return !is_null($this->merged_at);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeWaitingOnCustomer($query)
    {
        return $query->where('status', 'waiting_on_customer');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeNotMerged($query)
    {
        return $query->whereNull('merged_at');
    }
}
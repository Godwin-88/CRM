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
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ServiceRequest extends Model implements HasMedia
{
    use HasFactory, HasUlids, InteractsWithMedia, LogsActivity, SoftDeletes;

    const STATUS_SUBMITTED = 'submitted';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PENDING_CUSTOMER = 'pending_customer';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'catalog_item_id',
        'catalog_item_version_id',
        'requester_id',
        'contact_id',
        'account_id',
        'channel',
        'source_identifier',
        'status',
        'priority',
        'assigned_to',
        'team_id',
        'sla_instance_id',
        'form_response_id',
        'case_record_id',
        'closure_reason',
        'completed_at',
        'closed_at',
        'cancelled_at',
        'cancelled_reason',
        'reopened_at',
        'metadata',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reopened_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'priority' => 'medium',
        'status' => self::STATUS_SUBMITTED,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalogItem::class, 'catalog_item_id');
    }

    public function catalogItemVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalogItemVersion::class, 'catalog_item_version_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function slaInstance(): BelongsTo
    {
        return $this->belongsTo(SlaInstance::class, 'sla_instance_id');
    }

    public function formResponse(): BelongsTo
    {
        return $this->belongsTo(FormResponse::class, 'form_response_id');
    }

    public function caseRecord(): BelongsTo
    {
        return $this->belongsTo(CaseRecord::class, 'case_record_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ServiceRequestStatusHistory::class);
    }

    public function relatedRequests(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'service_request_links', 'service_request_id', 'linked_service_request_id')
            ->withPivot(['link_type', 'metadata'])
            ->withTimestamps();
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(ServiceRequestDocumentRequest::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->orderBy('created_at', 'asc');
    }

    public function changeStatus(string $status, ?User $user = null, ?string $reason = null): void
    {
        $previous = $this->status;
        $this->status = $status;

        if ($status === self::STATUS_PENDING_CUSTOMER) {
            $this->slaInstance?->pause('pending_customer');
        } elseif ($previous === self::STATUS_PENDING_CUSTOMER) {
            $this->slaInstance?->resume('customer_responded');
        }

        if ($status === self::STATUS_COMPLETED) {
            $this->completed_at ??= now();
        }

        if ($status === self::STATUS_CLOSED) {
            $this->closed_at ??= now();
        }

        $this->save();

        ServiceRequestStatusHistory::create([
            'service_request_id' => $this->id,
            'from_status' => $previous,
            'to_status' => $status,
            'transitioned_by_id' => $user?->id,
            'reason' => $reason,
        ]);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CLOSED, 'cancelled']);
    }

    public function scopeWaitingOnCustomer($query)
    {
        return $query->where('status', self::STATUS_PENDING_CUSTOMER);
    }
}

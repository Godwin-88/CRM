<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CaseRecord extends Model implements HasMedia
{
    use HasFactory, HasUlids, InteractsWithMedia, LogsActivity, SoftDeletes;

    const STATUS_NEW = 'new';
    const STATUS_TRIAGED = 'triaged';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PENDING_CUSTOMER = 'pending_customer';
    const STATUS_PENDING_INTERNAL = 'pending_internal';
    const STATUS_RESOLUTION_PROPOSED = 'resolution_proposed';
    const STATUS_CLOSED = 'closed';
    const STATUS_REOPENED = 'reopened';

    const TYPE_SERVICE_DELIVERY = 'service_delivery';
    const TYPE_COMPLAINT = 'complaint';
    const TYPE_COMPLIANCE = 'compliance';
    const TYPE_DISPUTE = 'dispute';
    const TYPE_INVESTIGATION = 'investigation';
    const TYPE_ESCALATION = 'escalation';
    const TYPE_CUSTOM = 'custom';

    protected $fillable = [
        'case_number',
        'title',
        'type',
        'priority',
        'status',
        'owner_id',
        'primary_contact_id',
        'primary_account_id',
        'sla_instance_id',
        'closure_report_id',
        'root_cause',
        'resolution_details',
        'closure_summary',
        'signoff_required',
        'signoff_status',
        'signoff_due_at',
        'signoff_approved_by_id',
        'signoff_approved_at',
        'closed_at',
        'reopened_at',
        'metadata',
    ];

    protected $casts = [
        'signoff_required' => 'boolean',
        'signoff_due_at' => 'datetime',
        'signoff_approved_at' => 'datetime',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'priority' => 'medium',
        'status' => self::STATUS_NEW,
        'type' => self::TYPE_SERVICE_DELIVERY,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }

    public function primaryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'primary_account_id');
    }

    public function slaInstance(): BelongsTo
    {
        return $this->belongsTo(SlaInstance::class, 'sla_instance_id');
    }

    public function closureReport(): BelongsTo
    {
        return $this->belongsTo(CaseClosureReport::class, 'closure_report_id');
    }

    public function signoffApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signoff_approved_by_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(CaseLink::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(CaseStatusHistory::class);
    }

    public function signoffs(): HasMany
    {
        return $this->hasMany(CaseSignoff::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->orderBy('created_at', 'asc');
    }

    public function changeStatus(string $status, ?User $user = null, ?string $reason = null): void
    {
        if ($status === self::STATUS_CLOSED && $this->signoff_required && $this->signoff_status !== 'approved') {
            throw new \RuntimeException('Case requires manager sign-off before closure.');
        }

        $previous = $this->status;
        $this->status = $status;

        if ($status === self::STATUS_CLOSED) {
            $this->closed_at ??= now();
            $this->signoff_status = $this->signoff_required ? 'approved' : null;
        }

        if ($status === self::STATUS_REOPENED) {
            $this->reopened_at ??= now();
        }

        $this->save();

        CaseStatusHistory::create([
            'case_record_id' => $this->id,
            'from_status' => $previous,
            'to_status' => $status,
            'transitioned_by_id' => $user?->id,
            'reason' => $reason,
        ]);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', '!=', self::STATUS_CLOSED);
    }

    public function scopePendingSignoff($query)
    {
        return $query->where('signoff_required', true)->where('signoff_status', 'pending');
    }
}

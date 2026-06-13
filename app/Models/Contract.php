<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Contract extends Model
{
    use HasUlids, Searchable, SoftDeletes;

    const STATUS_DRAFT = 'draft';

    const STATUS_SENT = 'sent';

    const STATUS_SIGNED = 'signed';

    const STATUS_ACTIVE = 'active';

    const STATUS_EXPIRING = 'expiring';

    const STATUS_EXPIRED = 'expired';

    const STATUS_DECLINED = 'declined';

    const STATUS_TERMINATED = 'terminated';

    const TYPE_MSA = 'msa';

    const TYPE_NDA = 'nda';

    const TYPE_SLA = 'sla';

    const TYPE_RENEWAL = 'renewal';

    const TYPE_UPSELL = 'upsell';

    const TYPE_CUSTOM = 'custom';

    public const TYPES = [
        self::TYPE_MSA,
        self::TYPE_NDA,
        self::TYPE_SLA,
        self::TYPE_RENEWAL,
        self::TYPE_UPSELL,
        self::TYPE_CUSTOM,
    ];

    protected $fillable = [
        'title',
        'account_id',
        'contact_id',
        'type',
        'status',
        'value',
        'currency',
        'start_date',
        'end_date',
        'document_path',
        'e_signature_status',
        'template_id',
        'created_by',
        'account_manager_id',
        'legal_matter_id',
        'suppress_reminders',
        'current_version',
        'activated_at',
        'terminated_at',
        'termination_reason',
        'custom_variables',
        'signed_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'suppress_reminders' => 'boolean',
        'activated_at' => 'datetime',
        'terminated_at' => 'datetime',
        'signed_at' => 'datetime',
        'custom_variables' => 'array',
    ];

    protected $with = ['account', 'contact', 'template'];

    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'type' => $this->type,
            'status' => $this->status,
            'account_name' => $this->account?->name ?? '',
            'contact_name' => trim(($this->contact?->first_name ?? '').' '.($this->contact?->last_name ?? '')),
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ContractVersion::class)->orderByDesc('version_number');
    }

    public function signatories(): HasMany
    {
        return $this->hasMany(ContractSignatory::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ContractMilestone::class);
    }

    public function kpiValues(): HasMany
    {
        return $this->hasMany(ContractKpiValue::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ContractTag::class, 'contract_contract_tag');
    }

    public function legalMatter(): BelongsTo
    {
        return $this->belongsTo(LegalMatter::class, 'legal_matter_id');
    }

    public function activeVersion(): HasMany
    {
        return $this->hasMany(ContractVersion::class)->where('status', 'active');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeExpiring($query)
    {
        return $query->where('status', self::STATUS_EXPIRING);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeSigned($query)
    {
        return $query->where('status', self::STATUS_SIGNED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeFilter($query, array $filters)
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }
        if (! empty($filters['account_manager_id'])) {
            $query->where('account_manager_id', $filters['account_manager_id']);
        }
        if (! empty($filters['start_date_from'])) {
            $query->whereDate('start_date', '>=', $filters['start_date_from']);
        }
        if (! empty($filters['start_date_to'])) {
            $query->whereDate('start_date', '<=', $filters['start_date_to']);
        }
        if (! empty($filters['end_date_from'])) {
            $query->whereDate('end_date', '>=', $filters['end_date_from']);
        }
        if (! empty($filters['end_date_to'])) {
            $query->whereDate('end_date', '<=', $filters['end_date_to']);
        }
        if (! empty($filters['value_min'])) {
            $query->where('value', '>=', $filters['value_min']);
        }
        if (! empty($filters['value_max'])) {
            $query->where('value', '<=', $filters['value_max']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('account', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('contact', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->end_date, false);
    }

    public function getDaysSinceExpiryAttribute(): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->end_date);
    }

    public function getIsExpiringAttribute(): bool
    {
        if (! $this->end_date) {
            return false;
        }

        $days = $this->days_remaining;

        return $days !== null && $days >= 0 && $days <= 90;
    }

    public function getIsExpiredAttribute(): bool
    {
        if (! $this->end_date) {
            return false;
        }

        return now()->startOfDay()->greaterThan($this->end_date);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_SENT => 'blue',
            self::STATUS_SIGNED => 'teal',
            self::STATUS_ACTIVE => 'green',
            self::STATUS_EXPIRING => 'amber',
            self::STATUS_EXPIRED => 'red',
            self::STATUS_DECLINED => 'coral',
            self::STATUS_TERMINATED => 'dark',
            default => 'gray',
        };
    }
}

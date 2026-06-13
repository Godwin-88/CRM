<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalMatter extends Model
{
    use HasUlids, SoftDeletes;

    const STATUS_OPEN = 'open';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_PENDING_EXTERNAL = 'pending_external';

    const STATUS_RESOLVED = 'resolved';

    const STATUS_CLOSED = 'closed';

    const TYPE_DISPUTE = 'dispute';

    const TYPE_CORRESPONDENCE = 'correspondence';

    const TYPE_REGULATORY = 'regulatory';

    const TYPE_ADVISORY = 'advisory';

    const TYPE_CUSTOM = 'custom';

    protected $fillable = [
        'subject',
        'description',
        'status',
        'type',
        'assigned_to',
        'account_id',
        'contact_id',
        'resolution_notes',
        'resolved_at',
        'closed_at',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $with = ['creator', 'assignee', 'account', 'contact'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LegalMatterNote::class, 'legal_matter_id')->orderByDesc('created_at');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'legal_matter_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}

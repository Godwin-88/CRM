<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractSignatory extends Model
{
    use HasUlids, SoftDeletes;

    const STATUS_PENDING = 'pending';

    const STATUS_VIEWED = 'viewed';

    const STATUS_SIGNED = 'signed';

    const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'contract_id',
        'contract_version_id',
        'user_id',
        'name',
        'email',
        'role',
        'status',
        'provider',
        'external_envelope_id',
        'signing_token',
        'signing_url',
        'viewed_at',
        'signed_at',
        'declined_at',
        'decline_reason',
        'ip_address',
        'user_agent',
        'signing_order',
        'is_sequential',
        'note',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'signed_at' => 'datetime',
        'declined_at' => 'datetime',
        'signing_order' => 'integer',
        'is_sequential' => 'boolean',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ContractVersion::class, 'contract_version_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

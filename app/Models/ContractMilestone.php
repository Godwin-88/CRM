<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractMilestone extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    const STATUS_PENDING = 'pending';

    const STATUS_COMPLETED = 'completed';

    const STATUS_MISSED = 'missed';

    protected $fillable = [
        'contract_id',
        'name',
        'description',
        'due_date',
        'assigned_party',
        'status',
        'assigned_to_type',
        'assigned_to_id',
        'completed_at',
        'completion_note',
        'is_notified',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'date',
        'is_notified' => 'boolean',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function assignee(): MorphTo
    {
        return $this->morphTo();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsLedger extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'enrollment_id',
        'contact_id',
        'program_id',
        'type',
        'points_amount',
        'running_balance',
        'description',
        'triggered_by_event',
        'metadata',
        'transaction_date',
        'created_by',
        'reason_note',
    ];

    protected $casts = [
        'points_amount' => 'integer',
        'running_balance' => 'integer',
        'metadata' => 'array',
        'transaction_date' => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(LoyaltyEnrollment::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyEnrollment extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'program_id',
        'contact_id',
        'enrolled_at',
        'unenrolled_at',
        'is_active',
    ];

    protected $casts = [
        'enrolled_at' => 'date',
        'unenrolled_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(PointsLedger::class);
    }
}

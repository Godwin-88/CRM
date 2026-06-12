<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyRule extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'program_id',
        'type',
        'name',
        'config',
        'points_amount',
        'multiplier',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'points_amount' => 'integer',
        'multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }
}

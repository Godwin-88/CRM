<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyRedemptionRule extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'program_id',
        'type',
        'name',
        'config',
        'min_points_threshold',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'min_points_threshold' => 'integer',
        'is_active' => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }
}

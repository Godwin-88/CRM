<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyProgram extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'program_type',
        'description',
        'currency_label',
        'currency_symbol',
        'earn_rate',
        'min_redemption_threshold',
        'is_active',
        'expiry_policy',
        'expiry_inactivity_months',
        'expiry_fixed_date',
        'matching_rules',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'earn_rate' => 'decimal:2',
        'min_redemption_threshold' => 'integer',
        'matching_rules' => 'array',
        'expiry_inactivity_months' => 'integer',
        'expiry_fixed_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(LoyaltyTier::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(LoyaltyRule::class);
    }

    public function redemptionRules(): HasMany
    {
        return $this->hasMany(LoyaltyRedemptionRule::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(LoyaltyEnrollment::class);
    }
}

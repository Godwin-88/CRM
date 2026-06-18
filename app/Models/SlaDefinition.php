<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlaDefinition extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'description',
        'priority',
        'support_category',
        'loyalty_tier_id',
        'account_type',
        'first_response_time_business_hours',
        'resolution_time_business_hours',
        'is_default',
    ];

    protected $casts = [
        'first_response_time_business_hours' => 'integer',
        'resolution_time_business_hours' => 'integer',
        'is_default' => 'boolean',
    ];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(LoyaltyTier::class, 'loyalty_tier_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'support_category');
    }

    public function businessHours(): HasMany
    {
        return $this->hasMany(BusinessHours::class);
    }

    public function instances(): HasMany
    {
        return $this->hasMany(SlaInstance::class);
    }
}

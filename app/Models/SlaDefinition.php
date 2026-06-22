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
        'target_entity_type',
        'service_catalog_item_id',
        'loyalty_tier_id',
        'account_type',
        'first_response_time_business_hours',
        'resolution_time_business_hours',
        'acknowledgement_time_business_hours',
        'review_time_business_hours',
        'next_action_time_business_hours',
        'completion_time_business_hours',
        'triage_time_business_hours',
        'investigation_update_time_business_hours',
        'resolution_proposal_time_business_hours',
        'closure_signoff_time_business_hours',
        'milestone_definitions',
        'is_default',
    ];

    protected $casts = [
        'first_response_time_business_hours' => 'integer',
        'resolution_time_business_hours' => 'integer',
        'acknowledgement_time_business_hours' => 'integer',
        'review_time_business_hours' => 'integer',
        'next_action_time_business_hours' => 'integer',
        'completion_time_business_hours' => 'integer',
        'triage_time_business_hours' => 'integer',
        'investigation_update_time_business_hours' => 'integer',
        'resolution_proposal_time_business_hours' => 'integer',
        'closure_signoff_time_business_hours' => 'integer',
        'milestone_definitions' => 'array',
        'is_default' => 'boolean',
    ];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(LoyaltyTier::class, 'loyalty_tier_id');
    }

    public function serviceCatalogItem(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalogItem::class, 'service_catalog_item_id');
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

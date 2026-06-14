<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClvCalculation extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'contact_id',
        'clv_score',
        'ltv',
        'historical_clv',
        'predicted_ltv',
        'total_revenue',
        'engagement_score',
        'loyalty_boost',
        'satisfaction_boost',
        'years_active',
        'avg_deal_value',
        'estimated_frequency',
        'estimated_lifespan_years',
        'churn_risk_score',
        'churn_risk_band',
        'calculated_at',
    ];

    protected $casts = [
        'clv_score' => 'decimal:2',
        'ltv' => 'decimal:2',
        'historical_clv' => 'decimal:2',
        'predicted_ltv' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'engagement_score' => 'integer',
        'loyalty_boost' => 'decimal:2',
        'satisfaction_boost' => 'decimal:2',
        'years_active' => 'decimal:2',
        'avg_deal_value' => 'decimal:2',
        'estimated_frequency' => 'decimal:2',
        'estimated_lifespan_years' => 'decimal:2',
        'churn_risk_score' => 'integer',
        'calculated_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}

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
        'historical_clv',
        'predicted_ltv',
        'avg_deal_value',
        'estimated_frequency',
        'estimated_lifespan_years',
        'churn_risk_score',
        'churn_risk_band',
        'calculated_at',
    ];

    protected $casts = [
        'historical_clv' => 'decimal:2',
        'predicted_ltv' => 'decimal:2',
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

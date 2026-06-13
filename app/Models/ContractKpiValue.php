<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractKpiValue extends Model
{
    use HasUlids;

    protected $fillable = [
        'contract_id',
        'contract_kpi_field_id',
        'value',
        'period_start',
        'period_end',
        'metadata',
        'recorded_by',
    ];

    protected $casts = [
        'value' => 'array',
        'metadata' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function kpiField(): BelongsTo
    {
        return $this->belongsTo(ContractKpiField::class, 'contract_kpi_field_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

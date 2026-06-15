<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractKpiField extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'contract_type',
        'name',
        'description',
        'type',
        'is_required',
        'settings',
        'options',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'settings' => 'array',
        'options' => 'array',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $contractType)
    {
        return $query->where('contract_type', $contractType);
    }
}

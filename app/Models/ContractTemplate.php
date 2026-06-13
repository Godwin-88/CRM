<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractTemplate extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'is_active',
        'created_by',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function clauses(): BelongsToMany
    {
        return $this->belongsToMany(ContractClause::class, 'contract_template_clause')
            ->withPivot('sort_order', 'is_mandatory', 'is_optional', 'is_included_by_default', 'template_variables')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ContractTemplateVersion::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}

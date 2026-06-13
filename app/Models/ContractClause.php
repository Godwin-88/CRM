<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractClause extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'body',
        'category',
        'type',
        'is_global',
        'is_active',
        'created_by',
        'variables',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'variables' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(ContractTemplate::class, 'contract_template_clause')
            ->withPivot('sort_order', 'is_mandatory', 'is_optional', 'is_included_by_default', 'template_variables')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}

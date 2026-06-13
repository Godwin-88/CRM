<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContractTag extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'usage_count',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class, 'contract_contract_tag');
    }
}

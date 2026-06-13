<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadSource extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'code',
        'acquisition_cost',
    ];

    protected $casts = [
        'acquisition_cost' => 'decimal:2',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'source');
    }
}
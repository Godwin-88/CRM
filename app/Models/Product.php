<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'category',
    ];

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}
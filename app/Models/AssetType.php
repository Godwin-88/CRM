<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetType extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'description',
        'is_stock_trackable',
    ];

    protected $casts = [
        'is_stock_trackable' => 'boolean',
    ];
}

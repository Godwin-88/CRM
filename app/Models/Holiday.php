<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'date',
        'name',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
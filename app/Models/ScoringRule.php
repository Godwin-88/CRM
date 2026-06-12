<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ScoringRule extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'entity_type',
        'field',
        'operator',
        'value',
        'points',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'points' => 'integer',
    ];
}
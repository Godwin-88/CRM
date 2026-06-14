<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataClassification extends Model
{
    protected $fillable = [
        'model_type',
        'field_name',
        'sensitivity',
    ];

    protected $casts = [
        'sensitivity' => 'string',
    ];
}

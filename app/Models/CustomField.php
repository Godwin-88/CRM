<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'type', // text, number, date, dropdown, boolean
        'options', // JSON array for dropdown options
        'entity_type', // Contact or Account class
        'entity_id', // optional: null means global
    ];

    protected $casts = [
        'options' => 'array',
    ];
}

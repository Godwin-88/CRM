<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomFieldValue extends Model
{
    use HasUlids;

    protected $fillable = [
        'customizable_type',
        'customizable_id',
        'field_key',
        'value',
    ];

    public function customizable(): MorphTo
    {
        return $this->morphTo();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessHours extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'sla_definition_id',
        'name',
        'days_of_week',
        'start_time',
        'end_time',
        'timezone',
        'is_global',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_global' => 'boolean',
    ];

    public function slaDefinition(): BelongsTo
    {
        return $this->belongsTo(SlaDefinition::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportDefinition extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'description',
        'owner_id',
        'visibility',
        'entity_type',
        'filters',
        'fields',
        'sort_field',
        'sort_direction',
        'group_by',
        'chart_type',
    ];

    protected $casts = [
        'filters' => 'array',
        'fields' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function scheduledDeliveries(): HasMany
    {
        return $this->hasMany(ScheduledReport::class, 'report_id');
    }
}

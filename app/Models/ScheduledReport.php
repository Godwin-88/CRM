<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduledReport extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'report_id',
        'frequency',
        'day_of_week',
        'day_of_month',
        'recipients',
        'last_run_at',
        'next_run_at',
        'enabled',
    ];

    protected $casts = [
        'recipients' => 'array',
        'last_run_at' => 'timestamp',
        'next_run_at' => 'timestamp',
        'enabled' => 'boolean',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ReportDefinition::class, 'report_id');
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(ReportDeliveryLog::class, 'scheduled_report_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportDeliveryLog extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'scheduled_report_id',
        'sent_at',
        'recipients',
        'row_count',
        'success',
        'error_message',
    ];

    protected $casts = [
        'recipients' => 'array',
        'sent_at' => 'timestamp',
        'success' => 'boolean',
    ];

    public function scheduledReport(): BelongsTo
    {
        return $this->belongsTo(ScheduledReport::class, 'scheduled_report_id');
    }
}
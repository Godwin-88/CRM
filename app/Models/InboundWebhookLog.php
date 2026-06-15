<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class InboundWebhookLog extends Model
{
    use HasUlids;

    protected $fillable = [
        'provider',
        'event_id',
        'signature_header',
        'payload',
        'status',
        'processing_error',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}

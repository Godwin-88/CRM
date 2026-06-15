<?php

namespace App\Events;

use App\Models\Webhook;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookEventOccurred
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $event,
        public array $payload,
    ) {}
}
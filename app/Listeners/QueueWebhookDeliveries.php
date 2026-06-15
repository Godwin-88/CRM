<?php

namespace App\Listeners;

use App\Events\WebhookEventOccurred;
use App\Jobs\DeliverWebhook;
use App\Models\Webhook;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueueWebhookDeliveries implements ShouldQueue
{
    public function handle(WebhookEventOccurred $event): void
    {
        $webhooks = Webhook::where('is_active', true)
            ->whereJsonContains('events', $event->event)
            ->get();

        foreach ($webhooks as $webhook) {
            DeliverWebhook::dispatch($webhook, $event->event, $event->payload);
        }
    }
}

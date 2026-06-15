<?php

namespace App\Jobs;

use App\Models\SecurityEvent;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $backoff = [60, 300, 1800, 7200]; // 1m, 5m, 30m, 2h

    public function __construct(
        protected Webhook $webhook,
        protected string $event,
        protected array $payload,
    ) {
        $this->onQueue('integrations');
    }

    public function handle(): void
    {
        $delivery = WebhookDelivery::create([
            'webhook_id' => $this->webhook->id,
            'event' => $this->event,
            'payload' => $this->payload,
            'attempt_number' => $this->attempts() + 1,
        ]);

        $response = Http::withHeaders([
            'X-Webhook-Signature' => 'sha256='.hash_hmac('sha256', json_encode($this->payload), $this->webhook->signing_secret),
            'Content-Type' => 'application/json',
        ])->timeout(10)->post($this->webhook->url, [
            'event' => $this->event,
            'timestamp' => now()->toIso8601String(),
            'data' => $this->payload,
        ]);

        $delivery->update([
            'response_status_code' => $response->status(),
            'response_time_ms' => $response->transferStats?->getTransferTime() * 1000,
            'response_body' => substr($response->body(), 0, 1024),
            'status' => $response->successful() ? 'delivered' : 'failed',
            'delivered_at' => $response->successful() ? now() : null,
            'failed_at' => $response->successful() ? null : now(),
        ]);

        $this->webhook->update([
            'last_success_at' => $response->successful() ? now() : $this->webhook->last_success_at,
            'last_failure_at' => $response->successful() ? $this->webhook->last_failure_at : now(),
            'consecutive_failures' => $response->successful()
                ? 0
                : $this->webhook->consecutive_failures + 1,
        ]);

        if ($this->webhook->auto_pause && $this->webhook->consecutive_failures > 50) {
            $this->webhook->update(['is_active' => false]);
            $this->logSecurityEvent('webhook_auto_paused', $this->webhook->creator, $this->webhook->name);
        }
    }

    protected function logSecurityEvent(string $event, $user, ?string $detail = null): void
    {
        SecurityEvent::create([
            'event_type' => $event,
            'user_id' => $user?->id,
            'email' => $user?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'outcome' => 'success',
            'metadata' => ['detail' => $detail],
        ]);
    }
}

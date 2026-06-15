<?php

namespace App\Jobs;

use App\Models\InboundWebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $backoff = [60, 300, 1800, 7200]; // 1m, 5m, 30m, 2h

    public function __construct(
        protected string $provider,
        protected array $payload,
        protected ?string $ip,
        protected ?string $signature,
    ) {
        $this->onQueue('integrations');
    }

    public function handle(): void
    {
        try {
            match ($this->provider) {
                'stripe' => $this->processStripe($this->payload),
                'twilio' => $this->processTwilio($this->payload),
                'docusign' => $this->processDocusign($this->payload),
                'mailgun' => $this->processMailgun($this->payload),
                default => $this->processGeneric($this->payload),
            };

            // Update log status
            InboundWebhookLog::where('event_id', $this->payload['id'] ?? null)
                ->where('provider', $this->provider)
                ->update(['status' => 'processed', 'processed_at' => now()]);

        } catch (\Exception $e) {
            Log::error("Webhook processing failed: {$this->provider}", [
                'error' => $e->getMessage(),
                'payload_id' => $this->payload['id'] ?? null,
            ]);

            InboundWebhookLog::where('event_id', $this->payload['id'] ?? null)
                ->where('provider', $this->provider)
                ->update([
                    'status' => 'failed',
                    'processing_error' => $e->getMessage(),
                    'processed_at' => now(),
                ]);

            throw $e;
        }
    }

    protected function processStripe(array $payload): void
    {
        // Handle Stripe events (invoice.payment_succeeded, customer.subscription_updated, etc.)
        // Create/update invoices, payments, customer records
    }

    protected function processTwilio(array $payload): void
    {
        // Handle Twilio webhooks (call completed, message received, etc.)
        // Create interactions, call logs
    }

    protected function processDocusign(array $payload): void
    {
        // Handle DocuSign events (envelope completed, declined, etc.)
        // Update contract signatures
    }

    protected function processMailgun(array $payload): void
    {
        // Handle Mailgun inbound emails
        // Create email interactions
    }

    protected function processGeneric(array $payload): void
    {
        // Generic handler for unknown webhook types
    }
}

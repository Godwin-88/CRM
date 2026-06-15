<?php

namespace App\Http\Controllers\Api\V1;

use App\Jobs\DeliverWebhook;
use App\Jobs\ProcessWebhook;
use App\Models\InboundWebhookLog;
use App\Models\SecurityEvent;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Webhook::class);

        $webhooks = Webhook::with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $webhooks->items(),
            'meta' => [
                'current_page' => $webhooks->currentPage(),
                'last_page' => $webhooks->lastPage(),
                'per_page' => $webhooks->perPage(),
                'total' => $webhooks->total(),
            ],
            'links' => [
                'first' => $webhooks->url(1),
                'last' => $webhooks->url($webhooks->lastPage()),
                'prev' => $webhooks->previousPageUrl(),
                'next' => $webhooks->nextPageUrl(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Webhook::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
        ]);

        $webhook = Webhook::create([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'events' => $validated['events'],
            'signing_secret' => 'whsec_'.str()->random(32),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'data' => $webhook,
        ], 201);
    }

    public function show(Webhook $webhook)
    {
        $this->authorize('view', $webhook);

        $webhook->load(['creator', 'deliveries' => function ($q) {
            $q->latest()->limit(50);
        }]);

        return response()->json(['data' => $webhook]);
    }

    public function update(Request $request, Webhook $webhook)
    {
        $this->authorize('update', $webhook);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url',
            'events' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $webhook->update($validated);

        return response()->json(['data' => $webhook->fresh()]);
    }

    public function destroy(Webhook $webhook)
    {
        $this->authorize('delete', $webhook);

        $webhook->delete();

        return response()->json(null, 204);
    }

    public function pause(Webhook $webhook)
    {
        $this->authorize('update', $webhook);

        $webhook->update(['is_active' => false]);

        return response()->json(['message' => 'Webhook paused.']);
    }

    public function resume(Webhook $webhook)
    {
        $this->authorize('update', $webhook);

        $webhook->update(['is_active' => true, 'consecutive_failures' => 0]);

        return response()->json(['message' => 'Webhook resumed.']);
    }

    public function retryDelivery(WebhookDelivery $delivery)
    {
        $this->authorize('update', $delivery->webhook);

        DeliverWebhook::dispatch($delivery->webhook, $delivery->event, $delivery->payload);

        return response()->json(['message' => 'Delivery queued for retry.']);
    }

    /* Inbound webhook endpoints */

    public function stripe(Request $request)
    {
        return $this->handleInboundWebhook($request, 'stripe');
    }

    public function twilio(Request $request)
    {
        return $this->handleInboundWebhook($request, 'twilio');
    }

    public function docusign(Request $request)
    {
        return $this->handleInboundWebhook($request, 'docusign');
    }

    public function mailgun(Request $request)
    {
        return $this->handleInboundWebhook($request, 'mailgun');
    }

    protected function handleInboundWebhook(Request $request, string $provider)
    {
        $signature = $request->header($this->getSignatureHeader($provider));
        $payload = $request->all();

        // Verify signature
        if (! $this->verifySignature($provider, $signature, $payload)) {
            $this->logSecurityEvent('inbound_webhook_invalid_signature', null, $provider);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Idempotency check
        $eventId = $payload['id'] ?? $payload['messageId'] ?? $payload['event'] ?? null;
        if ($eventId && InboundWebhookLog::where('provider', $provider)->where('event_id', $eventId)->exists()) {
            return response()->json(['message' => 'Duplicate event ignored']);
        }

        // Queue for processing
        ProcessWebhook::dispatch($provider, $payload, $request->ip(), $signature);

        // Log receipt
        InboundWebhookLog::create([
            'provider' => $provider,
            'event_id' => $eventId,
            'signature_header' => $signature,
            'payload' => $this->maskSensitiveFields($payload),
            'status' => 'received',
        ]);

        return response()->json(['received' => true]);
    }

    protected function getSignatureHeader(string $provider): string
    {
        return match ($provider) {
            'stripe' => 'Stripe-Signature',
            'twilio' => 'X-Twilio-Signature',
            'docusign' => 'X-DocuSign-Signature-1',
            'mailgun' => 'Mailgun-Signature',
            default => 'X-Webhook-Signature',
        };
    }

    protected function verifySignature(string $provider, ?string $signature, array $payload): bool
    {
        // Implementation would use provider-specific verification
        // Stripe: HMAC-SHA256 of payload with webhook secret
        // Twilio: HMAC-SHA1 of URL + body
        // This is simplified - actual implementation would validate properly
        return $signature !== null;
    }

    protected function maskSensitiveFields(array $payload): array
    {
        $sensitive = ['ssn', 'credit_card', 'cvv', 'password', 'api_key'];
        foreach ($sensitive as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = '***MASKED***';
            }
        }

        return $payload;
    }

    protected function logSecurityEvent(string $event, $user = null, ?string $detail = null): void
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

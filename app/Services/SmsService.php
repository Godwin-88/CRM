<?php

namespace App\Services;

use App\Models\Interaction;
use App\Models\UnmatchedItem;
use App\Models\Contact;
use App\Models\Integration;
use Illuminate\Support\Facades\Http;

class SmsService
{
    private ?string $defaultProvider = null;

    public function send(string $phone, string $message, ?string $contactId = null, ?string $agentId = null): Interaction
    {
        $contact = null;
        if ($contactId) {
            $contact = Contact::find($contactId);
        }

        $provider = $this->determineProvider($phone);
        $segments = ceil(mb_strlen($message) / 160);
        $warning = $segments > 1;

        $result = match ($provider) {
            'africastalking' => $this->sendViaAfricaTalking($phone, $message),
            'twilio' => $this->sendViaTwilio($phone, $message),
            default => throw new \Exception('No SMS provider configured'),
        };

        $interaction = Interaction::create([
            'contact_id' => $contactId,
            'type' => 'sms',
            'direction' => 'outbound',
            'subject' => 'SMS to ' . $phone,
            'body' => $message,
            'agent_id' => $agentId,
            'metadata' => [
                'provider' => $provider,
                'segments' => $segments,
                'segments_warning' => $warning,
                'provider_ref' => $result['reference'] ?? null,
            ],
        ]);

        return $interaction;
    }

    public function handleInboundWebhook(array $payload): ?Interaction
    {
        $from = $payload['from'] ?? null;
        $message = $payload['message'] ?? $payload['text'] ?? '';
        $providerMessageId = $payload['messageId'] ?? $payload['message_id'] ?? null;

        $contact = $this->findContactByPhone($from);

        $interactionData = [
            'type' => 'sms',
            'direction' => 'inbound',
            'subject' => 'SMS from ' . $from,
            'body' => $message,
            'agent_id' => null,
            'external_message_id' => $providerMessageId,
            'metadata' => [
                'from' => $from,
                'provider_ref' => $providerMessageId,
            ],
        ];

        if ($contact) {
            $interactionData['contact_id'] = $contact->id;
            if ($contact->account_id) {
                $interactionData['account_id'] = $contact->account_id;
            }
            $interaction = Interaction::create($interactionData);
        } else {
            UnmatchedItem::create([
                'source_type' => 'sms',
                'external_id' => $providerMessageId,
                'raw_payload' => $payload,
            ]);
            $interaction = Interaction::create($interactionData);
        }

        return $interaction;
    }

    private function determineProvider(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);

        // Kenya numbers start with +254 or 254
        if (str_starts_with($clean, '254')) {
            return 'africastalking';
        }

        return 'twilio';
    }

    private function sendViaAfricaTalking(string $phone, string $message): array
    {
        $integration = Integration::where('provider', 'africastalking')->where('is_active', true)->first();

        if (!$integration) {
            throw new \Exception('Africa\'s Talking integration not configured');
        }

        $config = $integration->config;
        $response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
            'username' => $config['username'],
            'to' => $phone,
            'message' => $message,
            'from' => $config['shortcode'] ?? null,
        ]);

        return [
            'reference' => $response->json('SMSMessageData->Recipients->0->messageId') ?? null,
        ];
    }

    private function sendViaTwilio(string $phone, string $message): array
    {
        $integration = Integration::where('provider', 'twilio')->where('is_active', true)->first();

        if (!$integration) {
            throw new \Exception('Twilio integration not configured');
        }

        $config = $integration->config;
        $response = Http::withBasicAuth($config['account_sid'], $config['auth_token'])
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$config['account_sid']}/Messages.json", [
                'To' => $phone,
                'From' => $config['phone_number'],
                'Body' => $message,
            ]);

        return [
            'reference' => $response->json('sid') ?? null,
        ];
    }

    private function findContactByPhone(?string $phone): ?Contact
    {
        if (!$phone) {
            return null;
        }
        $clean = preg_replace('/\D/', '', $phone);
        return Contact::whereRaw("REGEXP_REPLACE(phone, '\D', '') = ?", [$clean])->first();
    }
}

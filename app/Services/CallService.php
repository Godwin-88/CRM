<?php

namespace App\Services;

use App\Models\CallRecording;
use App\Models\Contact;
use App\Models\Interaction;
use App\Models\UnmatchedItem;

class CallService
{
    public function handleTwilioWebhook(array $payload): Interaction
    {
        $callSid = $payload['CallSid'] ?? null;
        $from = $payload['From'] ?? null;
        $to = $payload['To'] ?? null;
        $duration = (int) ($payload['CallDuration'] ?? 0);
        $recordingUrl = $payload['RecordingUrl'] ?? null;
        $direction = str_starts_with($callSid ?? '', 'IN') ? 'inbound' : 'outbound';

        $contact = $this->findContactByPhone($from ?? $to);

        $interactionData = [
            'type' => 'call',
            'direction' => $direction,
            'subject' => 'Call with '.($contact?->first_name ?? 'Unknown'),
            'duration_seconds' => $duration,
            'agent_id' => null,
            'metadata' => [
                'call_sid' => $callSid,
                'from' => $from,
                'to' => $to,
            ],
        ];

        if ($contact) {
            $interactionData['contact_id'] = $contact->id;
            if ($contact->account_id) {
                $interactionData['account_id'] = $contact->account_id;
            }
            $interaction = Interaction::create($interactionData);

            if ($recordingUrl) {
                $this->storeRecording($interaction, $callSid, $recordingUrl, $duration);
            }
        } else {
            // Create unmatched call
            UnmatchedItem::create([
                'source_type' => 'call',
                'external_id' => $callSid,
                'raw_payload' => $payload,
            ]);
            $interaction = Interaction::create($interactionData);
        }

        return $interaction;
    }

    private function findContactByPhone(?string $phone): ?Contact
    {
        if (! $phone) {
            return null;
        }
        $clean = preg_replace('/\D/', '', $phone);

        return Contact::whereRaw("REGEXP_REPLACE(phone, '\D', '') = ?", [$clean])->first();
    }

    private function storeRecording(Interaction $interaction, ?string $callSid, string $recordingUrl, int $duration): void
    {
        CallRecording::create([
            'interaction_id' => $interaction->id,
            'provider_call_sid' => $callSid,
            'recording_url' => $recordingUrl,
            'duration_seconds' => $duration,
        ]);
    }
}

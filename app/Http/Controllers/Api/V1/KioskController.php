<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Interaction;
use App\Models\KioskIntegration;
use App\Models\UnmatchedItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class KioskController extends Controller
{
    public function ingest(Request $request, KioskIntegration $kiosk): JsonResponse
    {
        $this->authorize('ingest', $kiosk);

        // Rate limit: 500 events per minute per kiosk
        $key = 'kiosk:'.$kiosk->id.':'.($request->ip() ?? 'unknown');
        if (RateLimiter::tooManyAttempts($key, 500)) {
            return response()->json(['message' => 'Rate limit exceeded'], 429);
        }
        RateLimiter::hit($key, 60);

        $validated = $request->validate([
            'event_type' => 'required|string|max:100',
            'customer_identifier' => 'required|string|max:255',
            'timestamp' => 'required|date',
            'event_metadata' => 'nullable|array',
        ]);

        $contact = $this->findContact($validated['customer_identifier']);

        $interaction = Interaction::create([
            'contact_id' => $contact?->id,
            'type' => 'kiosk',
            'direction' => 'inbound',
            'subject' => 'Kiosk event: '.$validated['event_type'],
            'body' => json_encode($validated['event_metadata'] ?? []),
            'agent_id' => null,
            'metadata' => [
                'kiosk_id' => $kiosk->id,
                'kiosk_name' => $kiosk->name,
                'event_type' => $validated['event_type'],
                'customer_identifier' => $validated['customer_identifier'],
                'event_metadata' => $validated['event_metadata'] ?? [],
            ],
        ]);

        if (! $contact) {
            UnmatchedItem::create([
                'source_type' => 'kiosk',
                'external_id' => $kiosk->id.'_'.$validated['customer_identifier'],
                'raw_payload' => $validated,
                'matched_contact_id' => null,
            ]);
        }

        return response()->json([
            'message' => 'Event ingested successfully',
            'interaction_id' => $interaction->id,
        ], 201);
    }

    private function findContact(string $identifier): ?Contact
    {
        // Try email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return Contact::where('email', $identifier)->first();
        }

        // Try phone
        $clean = preg_replace('/\D/', '', $identifier);
        if (strlen($clean) >= 10) {
            return Contact::whereRaw("REGEXP_REPLACE(phone, '\D', '') = ?", [$clean])->first();
        }

        // Try exact match on custom field (account number, etc.)
        return Contact::whereHas('customFieldValues', function ($q) use ($identifier) {
            $q->where('field_key', 'account_number')->where('value', $identifier);
        })->first();
    }
}

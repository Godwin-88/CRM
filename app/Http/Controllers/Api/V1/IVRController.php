<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Interaction;
use App\Models\UnmatchedItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class IVRController extends Controller
{
    public function ingest(Request $request): JsonResponse
    {
        $key = 'ivr:ingest:'.($request->ip());
        if (RateLimiter::tooManyAttempts($key, 200)) {
            return response()->json(['message' => 'Too many requests'], 429);
        }
        RateLimiter::hit($key, 60);

        $payload = $request->all();

        $expectedSignature = hash_hmac('sha256', json_encode($payload), config('services.ivr.secret', ''));
        if (!hash_equals($expectedSignature, $request->header('X-IVR-Signature', ''))) {
            $this->trackFailure($request->ip());
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $this->resetFailures($request->ip());

        $callerId = $payload['caller_id'] ?? null;
        $contact = null;
        if ($callerId) {
            $contact = Contact::where('phone', $callerId)->first();
        }

        $interaction = Interaction::create([
            'contact_id' => $contact?->id,
            'account_id' => $contact?->account_id,
            'type' => 'ivr',
            'direction' => 'inbound',
            'subject' => 'IVR Call - '.($payload['call_timestamp'] ?? now()->toDateTimeString()),
            'body' => $payload['transcript_text'] ?? '',
            'agent_id' => null,
            'metadata' => [
                'ivr_path' => $payload['ivr_path'] ?? [],
                'call_duration' => $payload['duration'] ?? 0,
                'caller_id' => $callerId,
            ],
        ]);

        if (! $contact) {
            UnmatchedItem::create([
                'source_type' => 'ivr',
                'raw_payload' => $payload,
                'status' => 'pending',
            ]);
        }

        return response()->json($interaction->load('contact'), 201);
    }

    private function trackFailure(string $ip): void
    {
        $key = "ivr:failures:{$ip}";
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, 3600);

        if ($count >= 5) {
            Log::channel('ivr')->alert("IVR ingestion failures for {$ip}", ['failures' => $count]);
            Cache::put("ivr:alerted:{$ip}", true, 3600);
        }
    }

    private function resetFailures(string $ip): void
    {
        Cache::forget("ivr:failures:{$ip}");
    }
}

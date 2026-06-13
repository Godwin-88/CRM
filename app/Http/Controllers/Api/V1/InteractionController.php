<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Interaction;
use App\Models\InteractionChannel;
use App\Models\UnmatchedItem;
use App\Services\CallService;
use App\Services\ChatService;
use App\Services\EmailService;
use App\Services\InteractionService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    public function __construct(
        protected InteractionService $interactionService,
        protected EmailService $emailService,
        protected CallService $callService,
        protected ChatService $chatService,
        protected SmsService $smsService,
    ) {}

    public function inbox(Request $request): JsonResponse
    {
        $agentId = auth()->id();
        $teamView = $request->boolean('team_view', false);

        $interactions = $this->interactionService->getInbox(
            $agentId,
            $teamView,
            $request->only(['channel', 'direction', 'date_from', 'date_to', 'contact_id', 'is_reviewed']),
            (int) $request->get('per_page', 50)
        );

        return response()->json($interactions);
    }

    public function show(string $id): JsonResponse
    {
        $interaction = $this->interactionService->getDetail($id);

        return response()->json($interaction);
    }

    public function markReviewed(string $id): JsonResponse
    {
        $agentId = auth()->id();
        $interaction = $this->interactionService->markAsReviewed($id, $agentId);

        return response()->json($interaction);
    }

    public function lock(string $id): JsonResponse
    {
        $agentId = auth()->id();
        $interaction = $this->interactionService->lockInteraction($id, $agentId);

        return response()->json($interaction);
    }

    public function unlock(string $id): JsonResponse
    {
        $agentId = auth()->id();
        $interaction = $this->interactionService->unlockInteraction($id, $agentId);

        return response()->json($interaction);
    }

    public function sendEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:email_templates,id',
            'contact_id' => 'required|exists:contacts,id',
            'deal_id' => 'nullable|exists:deals,id',
            'ticket_id' => 'nullable|exists:tickets,id',
            'variables' => 'nullable|array',
        ]);

        $interaction = $this->emailService->sendFromTemplate(
            $validated['template_id'],
            $validated['contact_id'],
            $validated['deal_id'] ?? null,
            $validated['ticket_id'] ?? null,
            $validated['variables'] ?? [],
            auth()->id()
        );

        activity()
            ->performedOn($interaction->contact)
            ->causedBy(auth()->user())
            ->event('email_sent')
            ->log('Email sent from CRM');

        return response()->json($interaction->load('contact', 'agent'), 201);
    }

    public function sendSms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'message' => 'required|string|max:1600',
        ]);

        $interaction = $this->smsService->send(
            $validated['contact_id'],
            $validated['message'],
            $validated['contact_id'],
            auth()->id()
        );

        activity()
            ->performedOn($interaction->contact)
            ->causedBy(auth()->user())
            ->event('sms_sent')
            ->log('SMS sent from CRM');

        return response()->json($interaction->load('contact', 'agent'), 201);
    }

    public function logCall(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'direction' => 'required|in:inbound,outbound',
            'call_date' => 'required|date',
            'duration_seconds' => 'nullable|integer|min:0',
            'outcome' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'deal_id' => 'nullable|exists:deals,id',
            'ticket_id' => 'nullable|exists:tickets,id',
        ]);

        $agentId = auth()->id();
        $callDate = $validated['call_date'];

        // Check if within 24-hour edit window
        $existing = Interaction::where('contact_id', $validated['contact_id'])
            ->where('type', 'call')
            ->where('direction', $validated['direction'])
            ->where('created_at', '>=', now()->subDay())
            ->orderByDesc('created_at')
            ->first();

        $interaction = $this->callService->handleTwilioWebhook([
            'From' => $validated['direction'] === 'inbound' ? 'manual' : 'agent',
            'To' => $validated['direction'] === 'outbound' ? 'manual' : 'agent',
            'CallSid' => 'manual_'.uniqid(),
            'CallDuration' => $validated['duration_seconds'] ?? 0,
            'call_date' => $callDate,
            'outcome' => $validated['outcome'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $interaction->update([
            'agent_id' => $agentId,
            'contact_id' => $validated['contact_id'],
            'deal_id' => $validated['deal_id'] ?? null,
            'body' => $validated['notes'] ?? '',
            'outcome' => $validated['outcome'] ?? null,
        ]);

        return response()->json($interaction->load('contact', 'agent'), 201);
    }

    public function unmatched(Request $request): JsonResponse
    {
        $this->authorize('viewAny', UnmatchedItem::class);

        $query = UnmatchedItem::query();

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->source_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate($request->get('per_page', 25)));
    }

    public function resolveUnmatched(Request $request, UnmatchedItem $item): JsonResponse
    {
        $this->authorize('update', UnmatchedItem::class);

        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'resolution_note' => 'nullable|string|max:1000',
        ]);

        $contact = Contact::findOrFail($validated['contact_id']);

        // Create inbound interaction from the unmatched item
        Interaction::create([
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'type' => $item->source_type,
            'direction' => 'inbound',
            'subject' => $item->raw_payload['subject'] ?? $item->raw_payload['from'] ?? 'Unmatched '.$item->source_type,
            'body' => $item->raw_payload['body'] ?? $item->raw_payload['message'] ?? '',
            'agent_id' => auth()->id(),
            'metadata' => $item->raw_payload,
        ]);

        $item->update([
            'matched_contact_id' => $contact->id,
            'assigned_to' => auth()->id(),
            'status' => 'resolved',
            'resolution_note' => $validated['resolution_note'] ?? null,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return response()->json(['message' => 'Unmatched item resolved and linked to contact.']);
    }

    public function channels(): JsonResponse
    {
        $channels = InteractionChannel::where('is_active', true)->get();

        return response()->json($channels);
    }
}

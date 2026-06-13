<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendLoyaltyCommunication;
use App\Models\Contact;
use App\Models\LoyaltyCommunicationLog;
use App\Models\LoyaltyCommunicationPreference;
use App\Models\LoyaltyCommunicationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyCommunicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LoyaltyCommunicationTemplate::query();

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }
        if ($request->filled('trigger_type')) {
            $query->where('trigger_type', $request->trigger_type);
        }
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', LoyaltyCommunicationTemplate::class);

        $validated = $request->validate([
            'program_id' => 'nullable|exists:loyalty_programs,id',
            'trigger_type' => 'required|in:tier_upgrade,tier_downgrade,points_earned,points_expiry_warning,redemption_confirmation',
            'channel' => 'required|in:email,sms,inapp',
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:500',
            'body' => 'required|string',
            'variables' => 'nullable|array',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status'] = 'pending_approval';

        $template = LoyaltyCommunicationTemplate::create($validated);

        return response()->json($template, 201);
    }

    public function show(LoyaltyCommunicationTemplate $template): JsonResponse
    {
        return response()->json($template);
    }

    public function update(Request $request, LoyaltyCommunicationTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);

        $validated = $request->validate([
            'program_id' => 'nullable|exists:loyalty_programs,id',
            'trigger_type' => 'sometimes|in:tier_upgrade,tier_downgrade,points_earned,points_expiry_warning,redemption_confirmation',
            'channel' => 'sometimes|in:email,sms,inapp',
            'name' => 'sometimes|string|max:255',
            'subject' => 'nullable|string|max:500',
            'body' => 'sometimes|string',
            'variables' => 'nullable|array',
        ]);

        $template->update($validated);

        return response()->json($template);
    }

    public function destroy(LoyaltyCommunicationTemplate $template): JsonResponse
    {
        $this->authorize('delete', $template);
        $template->delete();

        return response()->json(null, 204);
    }

    public function submitForReview(LoyaltyCommunicationTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);

        $template->update(['status' => 'pending_approval']);

        activity()
            ->performedOn($template)
            ->causedBy(auth()->user())
            ->event('submitted_for_review')
            ->log('Template submitted for approval');

        return response()->json(['message' => 'Template submitted for review.']);
    }

    public function approve(Request $request, LoyaltyCommunicationTemplate $template): JsonResponse
    {
        $this->authorize('approve', LoyaltyCommunicationTemplate::class);

        $validated = $request->validate([
            'approval_note' => 'nullable|string|max:1000',
        ]);

        $template->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_note' => $validated['approval_note'] ?? null,
        ]);

        activity()
            ->performedOn($template)
            ->causedBy(auth()->user())
            ->withProperties(['approval_note' => $validated['approval_note'] ?? null])
            ->event('approved')
            ->log('Template approved');

        return response()->json(['message' => 'Template approved.']);
    }

    public function reject(Request $request, LoyaltyCommunicationTemplate $template): JsonResponse
    {
        $this->authorize('approve', LoyaltyCommunicationTemplate::class);

        $validated = $request->validate([
            'approval_note' => 'required|string|max:1000',
        ]);

        $template->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_note' => $validated['approval_note'],
        ]);

        activity()
            ->performedOn($template)
            ->causedBy(auth()->user())
            ->withProperties(['approval_note' => $validated['approval_note']])
            ->event('rejected')
            ->log('Template rejected');

        return response()->json(['message' => 'Template rejected.']);
    }

    // One-off send
    public function send(Request $request, LoyaltyCommunicationTemplate $template): JsonResponse
    {
        $this->authorize('send', LoyaltyCommunicationTemplate::class);

        if ($template->status !== 'approved') {
            return response()->json(['message' => 'Template must be approved before sending.'], 422);
        }

        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $contacts = Contact::whereIn('id', $validated['contact_ids'])->get();
        $queued = 0;

        foreach ($contacts as $contact) {
            SendLoyaltyCommunication::dispatch($template, $contact);
            $queued++;
        }

        return response()->json(['message' => "{$queued} communications queued for sending."]);
    }

    // Contact preferences
    public function getPreference($contactId): JsonResponse
    {
        $contact = Contact::findOrFail($contactId);
        $this->authorize('view', $contact);

        $pref = LoyaltyCommunicationPreference::firstOrCreate(
            ['contact_id' => $contactId],
            ['loyalty_opt_out' => false]
        );

        return response()->json($pref);
    }

    public function updatePreference(Request $request, $contactId): JsonResponse
    {
        $contact = Contact::findOrFail($contactId);
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'loyalty_opt_out' => 'required|boolean',
        ]);

        $pref = LoyaltyCommunicationPreference::updateOrCreate(
            ['contact_id' => $contactId],
            array_merge($validated, ['loyalty_opt_out_updated_at' => now()])
        );

        return response()->json($pref);
    }

    // Logs
    public function logs(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LoyaltyCommunicationTemplate::class);

        $query = LoyaltyCommunicationLog::query()
            ->with(['contact', 'template']);

        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->contact_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        return response()->json($query->latest()->paginate($request->get('per_page', 25)));
    }
}

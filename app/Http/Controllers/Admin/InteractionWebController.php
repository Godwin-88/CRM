<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Interaction;
use App\Models\InteractionChannel;
use App\Models\UnmatchedItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InteractionWebController extends Controller
{
    public function index(): Response
    {
        $agentId = auth()->id();
        $teamView = request()->boolean('team_view', false);

        $query = Interaction::with(['contact', 'channel', 'deal', 'ticket', 'agent', 'attachments', 'contact.interactions' => function ($q) {
                $q->latest()->limit(3);
            }])
            ->orderBy('created_at', 'desc');

        if (!$teamView) {
            $query->where('agent_id', $agentId);
        }

$filters = request()->only(['type', 'channel', 'direction', 'date_from', 'date_to', 'contact_id', 'is_reviewed', 'agent_id']);
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['channel'])) {
            $query->where('channel_id', $filters['channel']);
        }
        if (!empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }
        if (!empty($filters['channel'])) {
            $query->where('channel_id', $filters['channel']);
        }
        if (!empty($filters['contact_id'])) {
            $query->where('contact_id', $filters['contact_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['is_reviewed'])) {
            $query->where('is_reviewed', $filters['is_reviewed']);
        }
        if (!empty($filters['agent_id']) && $teamView) {
            $query->where('agent_id', $filters['agent_id']);
        }

        $interactions = $query->paginate(50);
        $channels = InteractionChannel::orderBy('name')->get();
        $contacts = \App\Models\Contact::orderBy('first_name')->limit(100)->get(['id', 'first_name', 'last_name']);
        $agents = \App\Models\User::orderBy('name')->limit(100)->get(['id', 'name']);

        return Inertia::render('Admin/Interactions', [
            'interactions' => $interactions,
            'channels' => $channels,
            'contacts' => $contacts,
            'agents' => $agents,
            'filters' => $filters,
            'teamView' => $teamView,
        ]);
    }

    public function inbox(): Response
    {
        $items = UnmatchedItem::with(['channel', 'contact'])->orderBy('created_at', 'desc')->limit(100)->get();

        return Inertia::render('Admin/InteractionInbox', [
            'interactions' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'channel' => 'required|exists:interaction_channels,id',
            'direction' => 'required|in:inbound,outbound',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'contact_id' => 'nullable|exists:contacts,id',
            'occurred_at' => 'required|date',
            'agent_id' => 'nullable|exists:users,id',
        ]);

        $channel = InteractionChannel::find($validated['channel']);

        $data = [
            'channel_id' => $validated['channel'],
            'direction' => $validated['direction'],
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'agent_id' => $validated['agent_id'] ?? auth()->id(),
        ];

        if (!empty($validated['contact_id'])) {
            $contact = \App\Models\Contact::find($validated['contact_id']);
            $data['contact_id'] = $validated['contact_id'];
            $data['account_id'] = $contact?->account_id;
        }

        Interaction::create($data);

        return redirect()->route('admin.interactions.index')->with('success', 'Interaction logged successfully.');
    }

    public function unmatched(): Response
    {
        $items = UnmatchedItem::with(['channel'])->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/UnmatchedItems', [
            'items' => $items,
        ]);
    }

    public function resolveUnmatched(Request $request, UnmatchedItem $unmatchedItem)
    {
        $request->validate([
            'contact_id' => 'required|exists:contacts,id',
        ]);

        $unmatchedItem->update([
            'contact_id' => $request->contact_id,
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Unmatched item resolved successfully.');
    }

    public function channels(): Response
    {
        $channels = InteractionChannel::orderBy('name')->get();

        return Inertia::render('Admin/InteractionChannels', [
            'channels' => $channels,
        ]);
    }
}

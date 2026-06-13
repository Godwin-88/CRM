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
        $interactions = Interaction::with(['contact', 'channel'])->orderBy('created_at', 'desc')->limit(200)->get();

        return Inertia::render('Admin/Interactions', [
            'interactions' => $interactions,
        ]);
    }

    public function inbox(): Response
    {
        $interactions = Interaction::unmatched()->with(['channel'])->orderBy('created_at', 'desc')->limit(100)->get();

        return Inertia::render('Admin/InteractionInbox', [
            'interactions' => $interactions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'channel' => 'required|exists:interaction_channels,id',
            'direction' => 'required|in:inbound,outbound',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'contact_id' => 'nullable|exists:contacts,id',
            'occurred_at' => 'required|date',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->id();

        $interaction = Interaction::create($data);

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

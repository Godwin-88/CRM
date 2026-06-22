<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Segment;
use App\Models\User;
use App\Services\SegmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SegmentController extends Controller
{
    public function __construct(protected SegmentService $segmentService) {}

    public function index(): Response
    {
        return Inertia::render('Segments/Index', [
            'segments' => Segment::with(['campaign', 'creator'])->orderBy('created_at', 'desc')->get(),
            'campaigns' => Campaign::select('id', 'name', 'status')->orderBy('name')->get(),
            'users' => User::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:demographic,psychographic,behavioral,geographic,firmographic,technographic',
            'goal' => 'nullable|string|in:acquisition,retention,reactivation,upsell,cross_sell,loyalty,awareness,win_back',
            'status' => 'sometimes|string|in:draft,active,paused,archived',
            'criteria' => 'required|array',
            'criteria.rules' => 'required|array|min:1',
            'criteria.join_operator' => 'sometimes|in:and,or',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'channel_eligibility' => 'nullable|array',
            'channel_eligibility.*' => 'string|in:email,sms,push,in_app,whatsapp,facebook,instagram',
        ]);

        $segment = Segment::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'goal' => $data['goal'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'criteria' => $data['criteria'],
            'join_operator' => $data['criteria']['join_operator'] ?? 'and',
            'campaign_id' => $data['campaign_id'] ?? null,
            'tags' => $data['tags'] ?? [],
            'channel_eligibility' => $data['channel_eligibility'] ?? [],
            'contact_count' => 0,
            'created_by' => $request->user()?->id,
        ]);

        $this->segmentService->refreshCount($segment);

        return redirect()->route('segments.index')->with('success', 'Segment created successfully.');
    }

    public function update(Request $request, Segment $segment): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'sometimes|string|in:demographic,psychographic,behavioral,geographic,firmographic,technographic',
            'goal' => 'nullable|string|in:acquisition,retention,reactivation,upsell,cross_sell,loyalty,awareness,win_back',
            'status' => 'sometimes|string|in:draft,active,paused,archived',
            'criteria' => 'sometimes|array',
            'criteria.rules' => 'required_with:criteria|array|min:1',
            'criteria.join_operator' => 'sometimes|in:and,or',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'channel_eligibility' => 'nullable|array',
            'channel_eligibility.*' => 'string|in:email,sms,push,in_app,whatsapp,facebook,instagram',
        ]);

        $segment->update($data);
        $this->segmentService->refreshCount($segment);

        return redirect()->route('segments.index')->with('success', 'Segment updated successfully.');
    }

    public function destroy(Segment $segment): RedirectResponse
    {
        $segment->delete();
        return redirect()->route('segments.index')->with('success', 'Segment deleted.');
    }
}
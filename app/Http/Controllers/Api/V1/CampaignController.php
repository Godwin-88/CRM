<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignStep;
use App\Models\CampaignABTest;
use App\Models\CampaignTemplate;
use App\Models\CampaignRecipient;
use App\Models\DripEnrolment;
use App\Models\Segment;
use App\Services\SegmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    public function __construct(protected SegmentService $segmentService) {}

    public function index(): JsonResponse
    {
        $campaigns = Campaign::with(['segment', 'creator', 'steps.emailTemplate'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($campaigns);
    }

    public function show(Campaign $campaign): JsonResponse
    {
        $campaign->load(['segment', 'creator', 'steps.emailTemplate', 'abTest', 'socialPosts']);

        return response()->json($campaign);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:email,sms,push,in_app,multi_channel,social',
            'segment_id' => 'nullable|exists:segments,id',
            'scheduled_at' => 'nullable|date',
            'throttle_emails_per_hour' => 'integer|min:0',
            'throttle_sms_per_hour' => 'integer|min:0',
            'optimize_send_time' => 'boolean',
            'utm_source' => 'nullable|string',
            'utm_medium' => 'nullable|string',
            'utm_campaign' => 'nullable|string',
            'utm_term' => 'nullable|string',
            'utm_content' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $campaign = Campaign::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($campaign->load(['segment', 'creator']), 201);
    }

    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'segment_id' => 'sometimes|exists:segments,id',
            'scheduled_at' => 'sometimes|date',
            'throttle_emails_per_hour' => 'sometimes|integer|min:0',
            'throttle_sms_per_hour' => 'sometimes|integer|min:0',
            'optimize_send_time' => 'sometimes|boolean',
            'utm_source' => 'sometimes|string',
            'utm_medium' => 'sometimes|string',
            'utm_campaign' => 'sometimes|string',
            'utm_term' => 'sometimes|string',
            'utm_content' => 'sometimes|string',
            'tags' => 'sometimes|array',
        ]);

        $campaign->update($validated);

        return response()->json($campaign->load(['segment', 'creator']));
    }

    public function destroy(Campaign $campaign): JsonResponse
    {
        if (in_array($campaign->status, ['sending', 'sent'])) {
            return response()->json(['message' => 'Cannot delete campaign that is sending or already sent.'], 422);
        }

        $campaign->delete();

        return response()->json(null, 204);
    }

    public function addStep(Request $request, Campaign $campaign): JsonResponse
    {
        $validated = $request->validate([
            'channel' => 'required|in:email,sms,push,in_app',
            'email_template_id' => 'nullable|exists:campaign_templates,id',
            'sms_content' => 'nullable|string',
            'push_title' => 'nullable|string',
            'push_content' => 'nullable|string',
            'in_app_title' => 'nullable|string',
            'in_app_content' => 'nullable|string',
            'delay_type' => 'required|in:immediately,n_hours,n_days',
            'delay_value' => 'integer|min:0',
        ]);

        $position = $campaign->steps()->max('position') ?? 0;

        $step = CampaignStep::create([
            ...$validated,
            'campaign_id' => $campaign->id,
            'position' => $position + 1,
        ]);

        return response()->json($step->load(['emailTemplate']));
    }

    public function updateStep(Request $request, Campaign $campaign, CampaignStep $step): JsonResponse
    {
        if ($step->campaign_id !== $campaign->id) {
            return response()->json(['message' => 'Step does not belong to this campaign.'], 422);
        }

        $validated = $request->validate([
            'channel' => 'sometimes|in:email,sms,push,in_app',
            'email_template_id' => 'nullable|exists:campaign_templates,id',
            'sms_content' => 'nullable|string',
            'push_title' => 'nullable|string',
            'push_content' => 'nullable|string',
            'in_app_title' => 'nullable|string',
            'in_app_content' => 'nullable|string',
            'delay_type' => 'sometimes|in:immediately,n_hours,n_days',
            'delay_value' => 'sometimes|integer|min:0',
        ]);

        $step->update($validated);

        return response()->json($step->load(['emailTemplate']));
    }

    public function deleteStep(Campaign $campaign, CampaignStep $step): JsonResponse
    {
        if ($step->campaign_id !== $campaign->id) {
            return response()->json(['message' => 'Step does not belong to this campaign.'], 422);
        }

        $step->delete();

        return response()->json(null, 204);
    }

    public function reorderSteps(Request $request, Campaign $campaign): JsonResponse
    {
        $validated = $request->validate([
            'step_ids' => 'required|array',
            'step_ids.*' => 'exists:campaign_steps,id',
        ]);

        $stepIds = $validated['step_ids'];

        foreach ($stepIds as $position => $stepId) {
            CampaignStep::where('id', $stepId)
                ->where('campaign_id', $campaign->id)
                ->update(['position' => $position]);
        }

        return response()->json($campaign->steps()->orderBy('position')->get());
    }

    public function pause(Campaign $campaign): JsonResponse
    {
        if ($campaign->status !== 'sending') {
            return response()->json(['message' => 'Campaign is not sending.'], 422);
        }

        $campaign->update(['status' => 'paused']);

        return response()->json($campaign->load(['segment', 'creator']));
    }

    public function resume(Campaign $campaign): JsonResponse
    {
        if ($campaign->status !== 'paused') {
            return response()->json(['message' => 'Campaign is not paused.'], 422);
        }

        $campaign->update(['status' => 'sending']);

        return response()->json($campaign->load(['segment', 'creator']));
    }

    public function dispatch(Campaign $campaign): JsonResponse
    {
        if (!$campaign->canBeScheduled()) {
            return response()->json(['message' => 'Campaign must have a segment and at least one step with a template to dispatch.'], 422);
        }

        $campaign->update([
            'status' => 'scheduled',
            'scheduled_at' => $campaign->scheduled_at ?? now(),
        ]);

        return response()->json($campaign->load(['segment', 'creator']));
    }

    public function cancel(Campaign $campaign): JsonResponse
    {
        if (!in_array($campaign->status, ['scheduled', 'sending'])) {
            return response()->json(['message' => 'Campaign cannot be cancelled in its current status.'], 422);
        }

        $campaign->update(['status' => 'cancelled']);

        return response()->json($campaign->load(['segment', 'creator']));
    }

    public function validateCampaign(Campaign $campaign): JsonResponse
    {
        $errors = [];

        if (!$campaign->segment_id) {
            $errors[] = 'No target segment selected.';
        } else {
            $segment = $campaign->segment;
            $preview = $this->segmentService->getPreview($segment->criteria ?? ['rules' => []]);
            if (($preview['total_count'] ?? 0) === 0) {
                $errors[] = 'Target segment has zero matching contacts.';
            }
        }

        if (!$campaign->steps()->exists()) {
            $errors[] = 'Campaign has no steps defined.';
        }

        foreach ($campaign->steps as $step) {
            if ($step->channel === 'email' && !$step->emailTemplate) {
                $errors[] = "Step {$step->position} (email) has no template assigned.";
            }
        }

        return response()->json([
            'valid' => count($errors) === 0,
            'errors' => $errors,
        ]);
    }

    public function previewSegment(Segment $segment): JsonResponse
    {
        $criteria = $segment->criteria ?: ['rules' => []];
        $preview = $this->segmentService->getPreview($criteria);

        return response()->json($preview);
    }

    public function getSegmentCount(Segment $segment): JsonResponse
    {
        $preview = $this->segmentService->getPreview($segment->criteria ?? ['rules' => []]);

        return response()->json([
            'total_count' => $preview['total_count'] ?? 0,
            'email_eligible' => $preview['email_eligible'] ?? 0,
            'sms_eligible' => $preview['sms_eligible'] ?? 0,
            'push_eligible' => $preview['push_eligible'] ?? 0,
            'sample_contacts' => $preview['sample_contacts'] ?? [],
        ]);
    }
}

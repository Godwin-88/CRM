<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CampaignABTest;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CampaignTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignABTestController extends Controller
{
    public function index(): JsonResponse
    {
        $abTests = CampaignABTest::with(['campaign', 'variantATemplate', 'variantBTemplate'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($abTests);
    }

    public function store(Request $request, Campaign $campaign): JsonResponse
    {
        $validated = $request->validate([
            'test_type' => 'required|in:subject_line,content_variant,send_time',
            'winner_criterion' => 'required|in:open_rate,click_rate,conversion',
            'test_percentage' => 'required|integer|min:1|max:50',
            'duration_hours' => 'required|integer|min:1|max:72',
            'variant_a_template_id' => 'nullable|exists:campaign_templates,id',
            'variant_b_template_id' => 'nullable|exists:campaign_templates,id',
            'subject_line_a' => 'nullable|string',
            'subject_line_b' => 'nullable|string',
        ]);

        if ($campaign->abTest) {
            return response()->json(['message' => 'Campaign already has an A/B test configured.'], 422);
        }

        $abTest = CampaignABTest::create([
            ...$validated,
            'campaign_id' => $campaign->id,
            'status' => 'pending',
        ]);

        return response()->json($abTest->load(['campaign', 'variantATemplate', 'variantBTemplate']), 201);
    }

    public function show(Campaign $campaign): JsonResponse
    {
        $abTest = $campaign->abTest()->with(['variantATemplate', 'variantBTemplate'])->first();

        if (!$abTest) {
            return response()->json(['message' => 'No A/B test found for this campaign.'], 404);
        }

        return response()->json($abTest);
    }

    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $abTest = $campaign->abTest;

        if (!$abTest) {
            return response()->json(['message' => 'No A/B test found for this campaign.'], 404);
        }

        if ($abTest->status === 'running') {
            return response()->json(['message' => 'Cannot update a running A/B test.'], 422);
        }

        $validated = $request->validate([
            'test_type' => 'sometimes|in:subject_line,content_variant,send_time',
            'winner_criterion' => 'sometimes|in:open_rate,click_rate,conversion',
            'test_percentage' => 'sometimes|integer|min:1|max:50',
            'duration_hours' => 'sometimes|integer|min:1|max:72',
            'variant_a_template_id' => 'nullable|exists:campaign_templates,id',
            'variant_b_template_id' => 'nullable|exists:campaign_templates,id',
            'subject_line_a' => 'nullable|string',
            'subject_line_b' => 'nullable|string',
        ]);

        $abTest->update($validated);

        return response()->json($abTest->load(['variantATemplate', 'variantBTemplate']));
    }

    public function start(Campaign $campaign): JsonResponse
    {
        $abTest = $campaign->abTest;

        if (!$abTest) {
            return response()->json(['message' => 'No A/B test found for this campaign.'], 404);
        }

        if ($abTest->status !== 'pending') {
            return response()->json(['message' => 'A/B test is already running or concluded.'], 422);
        }

        $abTest->update(['status' => 'running', 'started_at' => now()]);

        return response()->json($abTest->load(['variantATemplate', 'variantBTemplate']));
    }

    public function conclude(Campaign $campaign): JsonResponse
    {
        $abTest = $campaign->abTest;

        if (!$abTest) {
            return response()->json(['message' => 'No A/B test found for this campaign.'], 404);
        }

        if ($abTest->status !== 'running') {
            return response()->json(['message' => 'A/B test is not running.'], 422);
        }

        $results = $this->calculateResults($abTest);
        $winner = $this->determineWinner($abTest, $results);

        $abTest->update([
            'status' => $winner ? 'concluded' : 'inconclusive',
            'winner_variant' => $winner,
            'concluded_at' => now(),
        ]);

        return response()->json($abTest->load(['variantATemplate', 'variantBTemplate']) + ['results' => $results]);
    }

    public function results(Campaign $campaign): JsonResponse
    {
        $abTest = $campaign->abTest()->with(['variantATemplate', 'variantBTemplate'])->first();

        if (!$abTest) {
            return response()->json(['message' => 'No A/B test found for this campaign.'], 404);
        }

        $results = $this->calculateResults($abTest);

        return response()->json([
            'ab_test' => $abTest,
            'results' => $results,
        ]);
    }

    private function calculateResults(CampaignABTest $abTest): array
    {
        $variantAEmails = CampaignRecipient::whereHas('step', function ($q) use ($abTest) {
                $q->where('campaign_id', $abTest->campaign_id);
            })
            ->where('status', '!=', 'pending')
            ->count();

        $variantAO = CampaignRecipient::whereHas('step', function ($q) use ($abTest) {
                $q->where('campaign_id', $abTest->campaign_id);
            })
            ->where('status', 'opened')
            ->count();

        $variantAC = CampaignRecipient::whereHas('step', function ($q) use ($abTest) {
                $q->where('campaign_id', $abTest->campaign_id);
            })
            ->where('status', 'clicked')
            ->count();

        $sent = max($variantAEmails, 1);
        $openRateA = round(($variantAO / $sent) * 100, 2);
        $clickRateA = round(($variantAC / $sent) * 100, 2);

        return [
            'variant_a' => [
                'sent' => $variantAEmails,
                'opened' => $variantAO,
                'clicked' => $variantAC,
                'open_rate' => $openRateA,
                'click_rate' => $clickRateA,
            ],
            'variant_b' => [
                'sent' => $variantAEmails,
                'opened' => $variantAO,
                'clicked' => $variantAC,
                'open_rate' => $openRateA,
                'click_rate' => $clickRateA,
            ],
        ];
    }

    private function determineWinner(CampaignABTest $abTest, array $results): ?string
    {
        $criterion = $abTest->winner_criterion;

        $scoreA = match ($criterion) {
            'open_rate' => $results['variant_a']['open_rate'],
            'click_rate' => $results['variant_a']['click_rate'],
            'conversion' => $results['variant_a']['click_rate'],
            default => 0,
        };

        $scoreB = match ($criterion) {
            'open_rate' => $results['variant_b']['open_rate'],
            'click_rate' => $results['variant_b']['click_rate'],
            'conversion' => $results['variant_b']['click_rate'],
            default => 0,
        };

        if ($scoreA > $scoreB) {
            return 'A';
        } elseif ($scoreB > $scoreA) {
            return 'B';
        }

        return null;
    }
}

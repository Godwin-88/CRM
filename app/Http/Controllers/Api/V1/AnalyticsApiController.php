<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Services\AnalyticsService;
use App\Services\FinanceAnalyticsService;
use App\Services\GrowthAnalyticsService;
use App\Services\PredictiveScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsApiController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService,
        protected GrowthAnalyticsService $growthAnalytics,
        protected FinanceAnalyticsService $financeAnalytics,
        protected PredictiveScoringService $scoringService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $role = $request->user()->getRoleNames()->first() ?? 'agent';
        $userId = $role === 'agent' ? $request->user()->id : null;
        $teamId = in_array($role, ['manager', 'admin']) ? $request->user()->team_id : null;

        return response()->json(
            $this->analyticsService->getDashboardMetrics($role, $userId, $teamId)
        );
    }

    public function growthMetrics(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'team_id', 'pipeline_id']);

        return response()->json([
            'lead_conversion' => $this->growthAnalytics->getLeadConversionMetrics($filters),
            'cac' => $this->growthAnalytics->getCACMetrics($filters),
            'ltv_to_cac' => $this->growthAnalytics->getLTVtoCACRatio($filters),
        ]);
    }

    public function financeMetrics(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'team_id', 'owner_id']);

        return response()->json([
            'revenue_by_product' => $this->financeAnalytics->getRevenueByProduct($filters),
            'revenue_by_account' => $this->financeAnalytics->getRevenueByAccount($filters),
            'revenue_trend' => $this->financeAnalytics->getRevenueTrend($filters),
            'revenue_allocation' => $this->financeAnalytics->getRevenueAllocation($filters),
            'ar_aging' => $this->financeAnalytics->getAccountsReceivableAging($filters),
        ]);
    }

    public function forecastWithTargets(Request $request): JsonResponse
    {
        $filters = $request->only(['owner_id', 'team_id', 'pipeline_id', 'close_from', 'close_to', 'period']);

        return response()->json([
            'forecast' => $this->analyticsService->getDashboardMetrics('agent'),
        ]);
    }

    public function dealScore(Deal $deal): JsonResponse
    {
        $scoring = $this->scoringService->calculateDealScore($deal);

        return response()->json([
            'deal_id' => $deal->id,
            'score' => $deal->manual_score ?? $deal->predicted_score ?? $scoring['score'],
            'label' => $this->getScoreLabel($deal->manual_score ?? $deal->predicted_score ?? $scoring['score']),
            'is_manual' => ! is_null($deal->manual_score),
            'signals' => $scoring['signals'],
        ]);
    }

    protected function getScoreLabel(int $score): string
    {
        return match (true) {
            $score <= 25 => 'cold',
            $score <= 50 => 'warm',
            $score <= 75 => 'hot',
            default => 'very_hot',
        };
    }
}

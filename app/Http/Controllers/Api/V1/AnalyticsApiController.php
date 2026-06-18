<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DashboardWidget;
use App\Models\Deal;
use App\Services\AnalyticsService;
use App\Services\FinanceAnalyticsService;
use App\Services\ForecastService;
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
        protected ForecastService $forecastService,
        protected PredictiveScoringService $scoringService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $this->roleFor($user);
        $filters = $request->only(['date_from', 'date_to', 'owner_id', 'contact_type', 'region', 'pipeline_stage', 'team_id']);

        if ($role === 'manager' && ! isset($filters['team_id'])) {
            $filters['team_id'] = $user->primaryTeam?->team_id;
        }
        if ($role === 'agent' && ! isset($filters['owner_id'])) {
            $filters['owner_id'] = $user->id;
        }

        return response()->json([
            ...$this->analyticsService->dashboardMetrics($role, $filters),
            'widgets' => $user->dashboardWidgets()->orderBy('position')->get(),
        ]);
    }

    public function dashboardWidgets(Request $request): JsonResponse
    {
        $widgets = $request->user()
            ->dashboardWidgets()
            ->orderBy('position')
            ->get()
            ->map(fn (DashboardWidget $widget) => [
                'id' => $widget->id,
                'widget_key' => $widget->widget_key,
                'position' => $widget->position,
                'settings' => $widget->settings ?? [],
                'enabled' => $widget->enabled,
            ]);

        return response()->json($widgets);
    }

    public function updateDashboardWidgets(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'widgets' => 'required|array',
            'widgets.*.widget_key' => 'required|string|max:255',
            'widgets.*.position' => 'required|integer|min:0',
            'widgets.*.settings' => 'nullable|array',
            'widgets.*.enabled' => 'boolean',
        ]);

        $widgets = collect($validated['widgets'])->map(function (array $widget) use ($request) {
            return DashboardWidget::updateOrCreate(
                ['user_id' => $request->user()->id, 'widget_key' => $widget['widget_key']],
                [
                    'position' => $widget['position'],
                    'settings' => $widget['settings'] ?? [],
                    'enabled' => $widget['enabled'] ?? true,
                ]
            );
        });

        return response()->json($widgets->values());
    }

    public function growthMetrics(Request $request): JsonResponse
    {
        $filters = $this->scopedFilters($request, ['date_from', 'date_to', 'team_id', 'owner_id', 'pipeline_id']);

        return response()->json([
            'lead_conversion' => $this->growthAnalytics->getLeadConversionMetrics($filters),
            'cac' => $this->growthAnalytics->getCACMetrics($filters),
            'ltv_to_cac' => $this->growthAnalytics->getLTVtoCACRatio($filters),
            'time_to_close' => $this->growthAnalytics->getTimeToCloseMetrics($filters),
        ]);
    }

    public function financeMetrics(Request $request): JsonResponse
    {
        $filters = $this->scopedFilters($request, ['date_from', 'date_to', 'team_id', 'owner_id', 'pipeline_id', 'product_category', 'account_id']);

        return response()->json([
            'revenue_by_product' => $this->financeAnalytics->getRevenueByProduct($filters),
            'revenue_by_account' => $this->financeAnalytics->getRevenueByAccount($filters),
            'revenue_by_agent' => $this->financeAnalytics->getRevenueByAgent($filters),
            'revenue_trend' => $this->financeAnalytics->getRevenueTrend($filters),
            'revenue_allocation' => $this->financeAnalytics->getRevenueAllocation($filters),
            'ar_aging' => $this->financeAnalytics->getAccountsReceivableAging($filters),
        ]);
    }

    public function customerMetrics(Request $request): JsonResponse
    {
        $filters = $this->scopedFilters($request, ['date_from', 'date_to', 'contact_type', 'loyalty_tier', 'owner_id', 'segment_id']);

        return response()->json([
            'segment_performance' => $this->analyticsService->getSegmentPerformance($filters),
            'churn_risk' => $this->analyticsService->getChurnRisk($filters),
            'cohort_retention' => $this->analyticsService->getCohortRetention($filters),
            'customer_journey' => $this->analyticsService->getCustomerJourneyMap($filters),
        ]);
    }

    public function forecastWithTargets(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $this->roleFor($user);
        $filters = $this->scopedFilters($request, ['owner_id', 'team_id', 'pipeline_id', 'close_from', 'close_to', 'period']);
        $teamId = $role === 'manager' ? $user->primaryTeam?->team_id : null;
        $canSeeTargets = $role !== 'agent';

        return response()->json(
            $this->forecastService->getForecastWithTargets($filters, $teamId, $canSeeTargets)
        );
    }

    public function updateRevenueTarget(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $this->roleFor($user);

        if ($role === 'agent') {
            abort(403);
        }

        $validated = $request->validate([
            'period' => 'required|string',
            'period_start' => 'required_without:custom_start|date',
            'period_end' => 'required_without:custom_end|date|after_or_equal:period_start',
            'custom_start' => 'date',
            'custom_end' => 'date|after_or_equal:custom_start',
            'target_revenue' => 'required|numeric|min:0',
        ]);

        $teamId = $role === 'manager' ? $user->primaryTeam?->team_id : null;

        $target = $this->forecastService->updateRevenueTarget($teamId, $validated);

        return response()->json($target);
    }

    public function dealScore(Deal $deal): JsonResponse
    {
        $this->authorizeDealAccess($deal);

        return response()->json($this->formatDealScore($deal));
    }

    public function scoringWeights(Request $request): JsonResponse
    {
        if ($this->roleFor($request->user()) === 'agent') {
            abort(403);
        }

        return response()->json([
            'weights' => $this->scoringService->getScoringWeights(),
        ]);
    }

    public function updateScoringWeights(Request $request): JsonResponse
    {
        if ($this->roleFor($request->user()) === 'agent') {
            abort(403);
        }

        $validated = $request->validate([
            'weights' => 'required|array',
            'weights.days_in_stage' => 'integer|min:0|max:100',
            'weights.recent_interactions' => 'integer|min:0|max:100',
            'weights.demo_trial_completed' => 'integer|min:0|max:100',
            'weights.deal_value' => 'integer|min:0|max:100',
            'weights.contact_engagement' => 'integer|min:0|max:100',
            'weights.days_to_close' => 'integer|min:0|max:100',
        ]);

        $this->scoringService->updateWeights($validated['weights']);

        return response()->json([
            'weights' => $this->scoringService->getScoringWeights(),
        ]);
    }

    public function dealScores(Request $request): JsonResponse
    {
        $filters = $this->scopedFilters($request, ['date_from', 'date_to', 'owner_id', 'pipeline_stage', 'team_id', 'region']);
        $query = Deal::query()
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->with(['contact', 'account', 'owner', 'pipelineStage']);

        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }
        if (isset($filters['team_id'])) {
            $query->whereHas('owner', fn ($q) => $q->whereHas('primaryTeam', fn ($team) => $team->where('team_id', $filters['team_id'])));
        }
        if (isset($filters['pipeline_stage'])) {
            $query->where('stage', $filters['pipeline_stage']);
        }
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return response()->json([
            'deals' => $query->limit(100)->get()->map(function (Deal $deal) {
                $score = $this->scoringService->calculateDealScore($deal);

                return [
                    'id' => $deal->id,
                    'title' => $deal->title,
                    'value' => $deal->value,
                    'stage' => $deal->stage,
                    'owner' => $deal->owner?->name,
                    'contact' => $deal->contact ? trim($deal->contact->first_name.' '.$deal->contact->last_name) : null,
                    'account' => $deal->account?->name,
                    'score' => $score['score'],
                    'label' => $score['label'],
                    'signals' => $score['signals'],
                    'manual_score' => $deal->manual_score,
                ];
            })->toArray(),
        ]);
    }

    public function recalculateDealScores(Request $request): JsonResponse
    {
        if ($this->roleFor($request->user()) === 'agent') {
            abort(403);
        }

        $this->scoringService->recalculateAllOpenDeals();

        return response()->json(['recalculated' => true]);
    }

    public function updateDealScore(Request $request, Deal $deal): JsonResponse
    {
        $this->authorizeDealAccess($deal);

        $validated = $request->validate([
            'score' => 'required|integer|min:0|max:100',
            'note' => 'nullable|string|max:1000',
        ]);

        $this->scoringService->setManualScore($deal, $validated['score'], $validated['note'] ?? '');

        return response()->json($this->formatDealScore($deal));
    }

    public function clearDealScore(Request $request, Deal $deal): JsonResponse
    {
        $this->authorizeDealAccess($deal);

        $this->scoringService->clearManualScore($deal);

        return response()->json($this->formatDealScore($deal));
    }

    protected function authorizeDealAccess(Deal $deal): void
    {
        $user = request()->user();
        $role = $this->roleFor($user);

        if ($role === 'admin') {
            return;
        }

        if ($role === 'manager' && $user->primaryTeam) {
            $isTeamDeal = $deal->owner && $deal->owner->primaryTeam?->team_id === $user->primaryTeam->team_id;

            if ($isTeamDeal) {
                return;
            }
        }

        if ($role === 'agent' && $deal->owner_id === $user->id) {
            return;
        }

        abort(403);
    }

    protected function formatDealScore(Deal $deal): array
    {
        $scoring = $this->scoringService->calculateDealScore($deal);
        $score = (int) ($deal->manual_score ?? $deal->predicted_score ?? $scoring['score']);

        return [
            'deal_id' => $deal->id,
            'score' => $score,
            'label' => $this->getScoreLabel($score),
            'is_manual' => ! is_null($deal->manual_score),
            'signals' => $scoring['signals'],
        ];
    }

    protected function roleFor($user): string
    {
        if ($user->is_admin) {
            return 'admin';
        }

        if ($user->primaryTeam) {
            return 'manager';
        }

        return 'agent';
    }

    protected function scopedFilters(Request $request, array $allowed): array
    {
        $user = $request->user();
        $role = $this->roleFor($user);
        $filters = array_filter($request->only($allowed), fn ($value) => $value !== null && $value !== '');

        if ($role === 'agent') {
            $filters['owner_id'] = $user->id;
        }

        if ($role === 'manager') {
            $filters['team_id'] = $user->primaryTeam?->team_id;
        }

        return array_filter($filters, fn ($value) => $value !== null && $value !== '');
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

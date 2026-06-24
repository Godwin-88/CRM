<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\ReportDefinition;
use App\Services\AnalyticsService;
use App\Services\ComplianceAnalyticsService;
use App\Services\ExploratoryAnalyticsService;
use App\Services\FinanceAnalyticsService;
use App\Services\ForecastService;
use App\Services\GrowthAnalyticsService;
use App\Services\PredictiveScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsWebController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService,
        protected GrowthAnalyticsService $growthAnalytics,
        protected FinanceAnalyticsService $financeAnalytics,
        protected ComplianceAnalyticsService $complianceAnalytics,
        protected PredictiveScoringService $predictiveScoring,
        protected ExploratoryAnalyticsService $exploratoryAnalytics,
        protected ForecastService $forecastService
    ) {}

    public function dashboard(Request $request): Response
    {
        $role = $this->roleFor($request->user());
        $filters = $request->only(['date_from', 'date_to', 'owner_id', 'contact_type', 'region', 'pipeline_stage', 'team_id']);

        if ($role === 'manager' && ! isset($filters['team_id'])) {
            $filters['team_id'] = $request->user()->primaryTeam?->team_id;
        }
        if ($role === 'agent' && ! isset($filters['owner_id'])) {
            $filters['owner_id'] = $request->user()->id;
        }

        return Inertia::render('Admin/Analytics/Dashboard', [
            'role' => $role,
            'metrics' => $this->analyticsService->dashboardMetrics($role, $filters),
            'widgets' => $request->user()->dashboardWidgets()->orderBy('position')->get(),
            'filters' => $filters,
        ]);
    }

    public function forecast(Request $request): Response
    {
        $role = $this->roleFor($request->user());
        $filters = $request->only(['period', 'owner_id', 'pipeline_stage', 'team_id', 'region']);
        $period = $request->get('period', 'current_quarter');
        $teamId = $role === 'manager' ? $request->user()->primaryTeam?->team_id : null;

        if ($role === 'manager' && ! isset($filters['team_id'])) {
            $filters['team_id'] = $teamId;
        }
        if ($role === 'agent' && ! isset($filters['owner_id'])) {
            $filters['owner_id'] = $request->user()->id;
        }

        return Inertia::render('Analytics/Forecast', [
            'role' => $role,
            'forecast' => $this->forecastService->getForecastWithTargets($filters, $teamId, $role !== 'agent'),
            'period' => $period,
            'filters' => $filters,
        ]);
    }

    public function customerAnalytics(Request $request): Response
    {
        $filters = $request->only(['date_from', 'date_to', 'contact_type', 'loyalty_tier', 'owner_id', 'segment_id']);
        $tab = $request->get('tab', 'segment_performance');

        return Inertia::render('Admin/Analytics/CustomerAnalytics', [
            'role' => $this->roleFor($request->user()),
            'tab' => $tab,
            'filters' => $filters,
            'segment_performance' => $this->analyticsService->getSegmentPerformance($filters),
            'churn_risk' => $this->analyticsService->getChurnRisk($filters),
            'cohort_retention' => $this->analyticsService->getCohortRetention($filters),
            'customer_journey' => $this->analyticsService->getCustomerJourneyMap($filters),
            'contact_types' => Contact::query()->distinct()->pluck('type')->filter()->values()->all(),
            'loyalty_tiers' => Contact::query()->distinct()->pluck('loyalty_tier')->filter()->values()->all(),
            'owners' => Contact::query()->distinct()->pluck('owner_id')->filter()->values()->all(),
            'last_calculated_at' => now()->toIso8601String(),
        ]);
    }

    public function growthAnalytics(Request $request): Response
    {
        $filters = $request->only(['date_from', 'date_to', 'team_id', 'pipeline_id']);

        return Inertia::render('Admin/Analytics/GrowthAnalytics', [
            'role' => $this->roleFor($request->user()),
            'lead_conversion' => $this->growthAnalytics->getLeadConversionMetrics($filters),
            'cac' => $this->growthAnalytics->getCACMetrics($filters),
            'ltv_to_cac' => $this->growthAnalytics->getLTVtoCACRatio($filters),
            'time_to_close' => $this->growthAnalytics->getTimeToCloseMetrics($filters),
            'last_calculated' => now()->toIso8601String(),
        ]);
    }

    public function financeAnalytics(Request $request): Response
    {
        $filters = $request->only(['date_from', 'date_to', 'team_id', 'owner_id', 'account_id', 'region', 'pipeline_stage']);
        $tab = $request->get('tab', 'revenue_by_product');

        return Inertia::render('Admin/Analytics/FinanceAnalytics', [
            'role' => $this->roleFor($request->user()),
            'tab' => $tab,
            'filters' => $filters,
            'revenue_by_product' => $this->financeAnalytics->getRevenueByProduct($filters),
            'revenue_by_account' => $this->financeAnalytics->getRevenueByAccount($filters),
            'revenue_by_agent' => $this->financeAnalytics->getRevenueByAgent($filters),
            'revenue_trend' => $this->financeAnalytics->getRevenueTrend($filters),
            'revenue_allocation' => $this->financeAnalytics->getRevenueAllocation($filters),
            'ar_aging' => $this->financeAnalytics->getAccountsReceivableAging($filters),
            'last_calculated' => now()->toIso8601String(),
        ]);
    }

    public function complianceAnalytics(Request $request): Response
    {
        $filters = $request->only(['date_from', 'date_to', 'user_id', 'event_type', 'ip_address', 'acknowledged']);
        $tab = $request->get('tab', 'anomalies');

        return Inertia::render('Admin/Analytics/ComplianceAnalytics', [
            'role' => $this->roleFor($request->user()),
            'tab' => $tab,
            'filters' => $filters,
            'audit_stats' => $this->complianceAnalytics->getAuditStats($filters),
            'anomalies' => $this->complianceAnalytics->detectAnomalies($filters),
            'audit_trail' => $this->complianceAnalytics->getAuditTrail($filters),
            'retention_settings' => $this->complianceAnalytics->retentionSettings(),
            'last_calculated' => now()->toIso8601String(),
        ]);
    }

    public function predictiveScoring(Request $request): Response
    {
        $filters = $request->only(['date_from', 'date_to', 'owner_id', 'pipeline_stage', 'team_id', 'region']);
        $tab = $request->get('tab', 'deal_scores');

        return Inertia::render('Admin/Analytics/PredictiveScoring', [
            'role' => $this->roleFor($request->user()),
            'tab' => $tab,
            'filters' => $filters,
            'weights' => $this->predictiveScoring->getScoringWeights(),
            'deal_scores' => $this->scoreOpenDeals($filters),
            'last_calculated' => now()->toIso8601String(),
        ]);
    }

    public function reportBuilder(Request $request): Response
    {
        return Inertia::render('Admin/Analytics/ReportBuilder', [
            'role' => $this->roleFor($request->user()),
            'report_definitions' => ReportDefinition::with('createdBy')->orderByDesc('created_at')->paginate(25),
            'sample_rows' => [],
            'export_formats' => ['csv', 'pdf'],
        ]);
    }

    public function storeReport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'entity_type' => ['required', 'string'],
            'visibility' => ['nullable', 'in:private,shared'],
            'fields' => ['nullable', 'array'],
            'fields.*' => ['string'],
            'group_by' => ['nullable', 'string'],
            'chart_type' => ['nullable', 'in:bar,line,pie,table'],
        ]);

        ReportDefinition::create([
            ...$validated,
            'owner_id' => $request->user()->id,
            'visibility' => $validated['visibility'] ?? 'private',
        ]);

        return back()->with('success', 'Report created.');
    }

    protected function scoreOpenDeals(array $filters = []): array
    {
        $query = Deal::query()
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->with(['contact', 'account', 'owner', 'pipelineStage']);

        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
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

        return $query->limit(100)->get()->map(function (Deal $deal) {
            $score = $this->predictiveScoring->calculateDealScore($deal);

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
        })->toArray();
    }

    protected function roleFor($user): string
    {
        if ($user->hasRole('admin')) {
            return 'admin';
        }

        if ($user->primaryTeam) {
            return 'manager';
        }

        return 'agent';
    }
}

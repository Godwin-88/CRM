<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Services\ComplianceAnalyticsService;
use App\Services\FinanceAnalyticsService;
use App\Services\GrowthAnalyticsService;
use App\Services\PredictiveScoringService;
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
        protected PredictiveScoringService $predictiveScoring
    ) {}

    public function dashboard(Request $request): Response
    {
        $role = $request->user()->getRoleNames()->first() ?? 'agent';
        $userId = $role === 'agent' ? $request->user()->id : null;
        $teamId = $role !== 'agent' ? $request->user()->team_id : null;

        $metrics = $this->analyticsService->getDashboardMetrics($role, $userId, $teamId);

        return Inertia::render('Admin/Analytics/Dashboard', $metrics);
    }

    public function customerAnalytics(Request $request): Response
    {
        $cohort = $this->analyticsService->getCohortRetention();

        return Inertia::render('Admin/Analytics/CustomerAnalytics', [
            'cohort_retention' => $cohort,
            'last_calculated' => now()->toIso8601String(),
        ]);
    }

    public function growthAnalytics(Request $request): Response
    {
        $filters = $request->only(['date_from', 'date_to', 'team_id', 'pipeline_id']);

        return Inertia::render('Admin/Analytics/GrowthAnalytics', [
            'lead_conversion' => $this->growthAnalytics->getLeadConversionMetrics($filters),
            'cac' => $this->growthAnalytics->getCACMetrics($filters),
            'ltv_to_cac' => $this->growthAnalytics->getLTVtoCACRatio($filters),
        ]);
    }

    public function financeAnalytics(Request $request): Response
    {
        $filters = $request->only(['date_from', 'date_to', 'team_id', 'owner_id']);

        return Inertia::render('Admin/Analytics/FinanceAnalytics', [
            'revenue_by_product' => $this->financeAnalytics->getRevenueByProduct($filters),
            'revenue_by_account' => $this->financeAnalytics->getRevenueByAccount($filters),
            'revenue_trend' => $this->financeAnalytics->getRevenueTrend($filters),
            'ar_aging' => $this->financeAnalytics->getAccountsReceivableAging($filters),
            'last_calculated' => now()->toIso8601String(),
        ]);
    }

    public function complianceAnalytics(Request $request): Response
    {
        $filters = $request->only(['date_from', 'date_to']);
        $stats = $this->complianceAnalytics->getAuditStats($filters);

        return Inertia::render('Admin/Analytics/ComplianceAnalytics', [
            'audit_stats' => $stats,
            'last_calculated' => now()->toIso8601String(),
        ]);
    }

    public function predictiveScoring(Request $request): Response
    {
        return Inertia::render('Admin/Analytics/PredictiveScoring', [
            'weights' => $this->predictiveScoring->getScoringWeights(),
        ]);
    }

    public function reportBuilder(Request $request): Response
    {
        return Inertia::render('Admin/Analytics/ReportBuilder');
    }
}

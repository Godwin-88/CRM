<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\ClvCalculation;
use App\Models\Contact;
use App\Models\Deal;
use Carbon\Carbon;

class GrowthAnalyticsService
{
    public function getLeadConversionMetrics(array $filters = []): array
    {
        $query = Contact::query();

        $this->applyDateFilters($query, $filters);
        $this->applyTeamFilters($query, $filters);

        $totalLeads = $query->where('type', 'lead')->count();

        $leadsToDeals = Contact::where('type', 'lead')
            ->whereHas('deals', fn ($q) => $q->where('created_at', '>=', $this->getStartDate($filters)))
            ->count();

        $dealsToWon = Deal::query()
            ->where('stage', 'closed_won')
            ->where('created_at', '>=', $this->getStartDate($filters));

        $this->applyTeamFilters($dealsToWon, $filters);

        $totalDeals = Deal::where('created_at', '>=', $this->getStartDate($filters))->count();

        return [
            'total_leads' => $totalLeads,
            'lead_to_opportunity_rate' => $totalLeads > 0 ? round($leadsToDeals / $totalLeads * 100, 2) : 0,
            'opportunity_to_won_rate' => $totalDeals > 0 ? round($dealsToWon->count() / $totalDeals * 100, 2) : 0,
            'lead_to_customer_rate' => $totalLeads > 0 && $dealsToWon->count() > 0
                ? round($dealsToWon->count() / $totalLeads * 100, 2)
                : 0,
            'conversion_funnel' => $this->getConversionFunnel($filters),
        ];
    }

    public function getCACMetrics(array $filters = []): array
    {
        $startDate = $this->getStartDate($filters);
        $endDate = $this->getEndDate($filters);

        $campaignSpend = Campaign::where('started_at', '>=', $startDate)
            ->where('started_at', '<=', $endDate)
            ->sum('spent');

        $newCustomers = Contact::where('type', 'customer')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->count();

        $cac = $newCustomers > 0 ? round($campaignSpend / $newCustomers, 2) : 0;

        return [
            'cac' => $cac,
            'campaign_spend' => $campaignSpend,
            'new_customers' => $newCustomers,
            'cac_trend' => $this->getCACTrend($filters),
        ];
    }

    public function getLTVtoCACRatio(array $filters = []): array
    {
        $cacData = $this->getCACMetrics($filters);
        $cac = $cacData['cac'];

        $avgLTV = ClvCalculation::whereHas('contact', fn ($q) => $q->where('created_at', '>=', $this->getStartDate($filters))
        )->avg('predicted_ltv') ?? 0;

        $ratio = $cac > 0 ? round($avgLTV / $cac, 2) : 0;

        return [
            'ratio' => $ratio,
            'avg_ltv' => $avgLTV,
            'cac' => $cac,
            'status' => $ratio >= 3 ? 'good' : ($ratio >= 1 ? 'warning' : 'critical'),
        ];
    }

    protected function getConversionFunnel(array $filters = []): array
    {
        $startDate = $this->getStartDate($filters);

        $stages = [
            'lead' => Contact::where('type', 'lead')->where('created_at', '>=', $startDate)->count(),
            'opportunity' => Contact::where('type', 'lead')->whereHas('deals')->where('created_at', '>=', $startDate)->count(),
            'proposal' => Deal::where('stage', 'proposal_sent')->where('created_at', '>=', $startDate)->count(),
            'closed_won' => Deal::where('stage', 'closed_won')->where('created_at', '>=', $startDate)->count(),
        ];

        return $stages;
    }

    protected function getCACTrend(array $filters = []): array
    {
        $trend = [];

        for ($i = 2; $i >= 0; $i--) {
            $start = Carbon::now()->subMonths($i + 2)->startOfMonth();
            $end = Carbon::now()->subMonths($i + 1)->endOfMonth();

            $spend = Campaign::where('started_at', '>=', $start)
                ->where('started_at', '<=', $end)
                ->sum('spent');

            $customers = Contact::where('type', 'customer')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<=', $end)
                ->count();

            $trend[] = [
                'month' => $start->format('Y-m'),
                'cac' => $customers > 0 ? round($spend / $customers, 2) : 0,
            ];
        }

        return $trend;
    }

    protected function applyDateFilters($query, array $filters): void
    {
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
    }

    protected function applyTeamFilters($query, array $filters): void
    {
        if (isset($filters['team_id'])) {
            $query->whereHas('owner', fn ($q) => $q->where('team_id', $filters['team_id']));
        }
        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }
    }

    protected function getStartDate(array $filters): Carbon
    {
        return isset($filters['date_from'])
            ? Carbon::parse($filters['date_from'])
            : Carbon::now()->subMonth();
    }

    protected function getEndDate(array $filters): ?Carbon
    {
        return isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : now();
    }
}

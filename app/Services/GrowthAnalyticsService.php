<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\ClvCalculation;
use App\Models\Contact;
use App\Models\Deal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GrowthAnalyticsService
{
    public function getLeadConversionMetrics(array $filters = []): array
    {
        $startDate = $this->getStartDate($filters);
        $endDate = $this->getEndDate($filters);

        $contactQuery = Contact::query()->whereBetween('created_at', [$startDate, $endDate]);
        $this->applyTeamFilters($contactQuery, $filters);

        $totalLeads = (clone $contactQuery)->where('type', 'lead')->count();

        $leadContacts = Contact::query()
            ->where('type', 'lead')
            ->whereBetween('created_at', [$startDate, $endDate]);
        $this->applyTeamFilters($leadContacts, $filters);

        $opportunityContacts = (clone $leadContacts)
            ->whereHas('deals', fn ($q) => $q->whereBetween('created_at', [$startDate, $endDate]));

        $dealsQuery = Deal::query()->whereBetween('created_at', [$startDate, $endDate]);
        $this->applyFilters($dealsQuery, $filters);

        $wonDeals = (clone $dealsQuery)->where('stage', 'closed_won');

        return [
            'total_leads' => $totalLeads,
            'lead_to_opportunity_rate' => $totalLeads > 0 ? round($opportunityContacts->count() / $totalLeads * 100, 2) : 0,
            'opportunity_to_won_rate' => $dealsQuery->count() > 0 ? round($wonDeals->count() / $dealsQuery->count() * 100, 2) : 0,
            'lead_to_customer_rate' => $totalLeads > 0 ? round($this->getNewCustomerCount($filters, $startDate, $endDate) / $totalLeads * 100, 2) : 0,
            'conversion_funnel' => $this->getConversionFunnel($filters, $startDate, $endDate),
        ];
    }

    public function getCACMetrics(array $filters = []): array
    {
        $startDate = $this->getStartDate($filters);
        $endDate = $this->getEndDate($filters);

        $campaignQuery = Campaign::query()->whereBetween('started_at', [$startDate, $endDate]);
        $this->applyCampaignFilters($campaignQuery, $filters);

        $campaignSpend = $campaignQuery->sum('spent');
        $newCustomers = $this->getNewCustomerCount($filters, $startDate, $endDate);
        $cac = $newCustomers > 0 ? round($campaignSpend / $newCustomers, 2) : 0;
        $trend = $this->getCACTrend($filters);
        $rollingAverage = collect($trend)->filter(fn ($row) => $row['cac'] > 0)->avg('cac') ?: 0;

        return [
            'cac' => $cac,
            'campaign_spend' => (float) $campaignSpend,
            'new_customers' => $newCustomers,
            'cac_trend' => $trend,
            'rolling_3_month_average' => round($rollingAverage, 2),
        ];
    }

    public function getLTVtoCACRatio(array $filters = []): array
    {
        $cacData = $this->getCACMetrics($filters);
        $cac = $cacData['cac'];

        $avgLTV = ClvCalculation::query()
            ->whereHas('contact', function ($q) use ($filters) {
                $this->applyTeamFilters($q, $filters);
                if (isset($filters['date_from'])) {
                    $q->where('created_at', '>=', $filters['date_from']);
                }
                if (isset($filters['date_to'])) {
                    $q->where('created_at', '<=', $filters['date_to']);
                }
            })
            ->avg('predicted_ltv') ?? 0;

        $ratio = $cac > 0 ? round($avgLTV / $cac, 2) : 0;

        return [
            'ratio' => $ratio,
            'avg_ltv' => (float) $avgLTV,
            'cac' => $cac,
            'status' => $ratio >= 3 ? 'good' : ($ratio >= 1 ? 'warning' : 'critical'),
        ];
    }

    public function getTimeToCloseMetrics(array $filters = []): array
    {
        $startDate = $this->getStartDate($filters);
        $endDate = $this->getEndDate($filters);

        $query = Deal::query()
            ->where('stage', 'closed_won')
            ->whereBetween('created_at', [$startDate, $endDate]);
        $this->applyFilters($query, $filters);

        $deals = $query->get();
        $byPipeline = $deals->groupBy('pipeline_id')->map(fn ($dealsForPipeline) => round($dealsForPipeline->avg(fn (Deal $deal) => $this->daysToClose($deal)), 2));
        $byValueBand = $deals->groupBy(fn (Deal $deal) => $this->valueBand($deal->value))->map(fn ($dealsForBand) => round($dealsForBand->avg(fn (Deal $deal) => $this->daysToClose($deal)), 2));

        return [
            'average_days' => $deals->isNotEmpty() ? round($deals->avg(fn (Deal $deal) => $this->daysToClose($deal)), 2) : 0,
            'by_pipeline' => $byPipeline->toArray(),
            'by_value_band' => $byValueBand->toArray(),
        ];
    }

    protected function getConversionFunnel(array $filters, Carbon $startDate, Carbon $endDate): array
    {
        $baseQuery = Deal::query()->whereBetween('created_at', [$startDate, $endDate]);
        $this->applyFilters($baseQuery, $filters);

        $stages = PipelineStageOrder::STAGES;
        $funnel = [];

        foreach ($stages as $stage) {
            $query = (clone $baseQuery)->where('stage', $stage);
            $funnel[$stage] = $query->count();
        }

        $funnel['closed_won'] = (clone $baseQuery)->where('stage', 'closed_won')->count();

        return $funnel;
    }

    protected function getCACTrend(array $filters): array
    {
        $trend = [];

        for ($i = 2; $i >= 0; $i--) {
            $start = Carbon::now()->subMonths($i + 2)->startOfMonth();
            $end = Carbon::now()->subMonths($i + 1)->endOfMonth();
            $spend = Campaign::whereBetween('started_at', [$start, $end])->sum('spent');
            $customers = Contact::where('type', 'customer')
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $trend[] = [
                'month' => $start->format('Y-m'),
                'cac' => $customers > 0 ? round($spend / $customers, 2) : 0,
            ];
        }

        return $trend;
    }

    protected function getNewCustomerCount(array $filters, Carbon $startDate, Carbon $endDate): int
    {
        $query = Contact::query()
            ->where('type', 'customer')
            ->whereBetween('created_at', [$startDate, $endDate]);

        $this->applyTeamFilters($query, $filters);

        return $query->count();
    }

    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['pipeline_id'])) {
            $query->where('pipeline_id', $filters['pipeline_id']);
        }

        $this->applyTeamFilters($query, $filters);
    }

    protected function applyTeamFilters($query, array $filters): void
    {
        if (isset($filters['team_id'])) {
            $query->whereHas('owner', fn ($q) => $q->whereHas('primaryTeam', fn ($team) => $team->where('team_id', $filters['team_id'])));
        }
        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }
    }

    protected function applyCampaignFilters($query, array $filters): void
    {
        if (isset($filters['team_id'])) {
            $query->whereHas('creator', fn ($q) => $q->whereHas('primaryTeam', fn ($team) => $team->where('team_id', $filters['team_id'])));
        }
        if (isset($filters['owner_id'])) {
            $query->where('created_by', $filters['owner_id']);
        }
    }

    protected function getStartDate(array $filters): Carbon
    {
        return isset($filters['date_from']) ? Carbon::parse($filters['date_from']) : Carbon::now()->subMonth();
    }

    protected function getEndDate(array $filters): Carbon
    {
        return isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : Carbon::now();
    }

    protected function daysToClose(Deal $deal): int
    {
        return $deal->created_at && $deal->updated_at
            ? max(0, $deal->created_at->diffInDays($deal->updated_at))
            : 0;
    }

    protected function valueBand(float $value): string
    {
        return match (true) {
            $value < 10000 => '0-10k',
            $value < 50000 => '10k-50k',
            $value < 100000 => '50k-100k',
            default => '100k+',
        };
    }
}

class PipelineStageOrder
{
    public const STAGES = ['qualification', 'demo', 'proposal', 'negotiation'];
}

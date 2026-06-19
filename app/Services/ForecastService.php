<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\PipelineStage;
use App\Models\RevenueTarget;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ForecastService
{
    public function getRevenueForecast(array $filters = []): array
    {
        $period = $this->resolvePeriod($filters);
        $query = $this->forecastQuery($filters, $period);

        $deals = $query->with(['account', 'owner', 'pipelineStage'])->get();
        $totalWeighted = $deals->sum(fn (Deal $deal) => $deal->getWeightedValue());
        $totalBestCase = $deals->sum('value');

        $byStage = $deals->groupBy('stage')->map(fn ($stageDeals, $stage) => [
            'stage' => $stage,
            'count' => $stageDeals->count(),
            'total_value' => $stageDeals->sum('value'),
            'weighted_value' => $stageDeals->sum(fn (Deal $deal) => $deal->getWeightedValue()),
            'best_case_value' => $stageDeals->sum('value'),
        ])->values();

        return [
            'period' => $filters['period'] ?? 'current_month',
            'period_start' => $period['start']->toDateString(),
            'period_end' => $period['end']->toDateString(),
            'total_unweighted' => $totalBestCase,
            'total_weighted' => $totalWeighted,
            'best_case_value' => $totalBestCase,
            'by_stage' => $byStage->toArray(),
            'deals' => $deals->map(fn (Deal $deal) => $this->formatDealForecastRow($deal))->toArray(),
        ];
    }

    public function getForecastWithTargets(array $filters = [], ?string $teamId = null, bool $canSeeTargets = true): array
    {
        $forecast = $this->getRevenueForecast($filters);
        $target = $canSeeTargets ? $this->getRevenueTarget($filters['period'] ?? 'current_month', $forecast['period_start'], $forecast['period_end'], $teamId) : null;

        $weighted = (float) $forecast['total_weighted'];
        $targetValue = (float) ($target['target_revenue'] ?? 0);
        $gap = $targetValue - $weighted;
        $gapPercentage = $targetValue > 0 ? round(($gap / $targetValue) * 100, 2) : 0;

        return [
            ...$forecast,
            'target_revenue' => $targetValue,
            'forecast_gap' => $gap,
            'forecast_gap_percentage' => $gapPercentage,
            'gap_status' => $this->gapStatus($weighted, $targetValue),
            'target' => $target,
        ];
    }

    public function getTimeBucketedForecast(array $filters = []): array
    {
        $query = Deal::query()->whereNotIn('stage', ['closed_won', 'closed_lost']);
        $this->applyFilters($query, $filters);

        $buckets = $query->select(
            DB::raw("TO_CHAR(expected_close_date, 'YYYY-MM') as month"),
            DB::raw('sum(value) as total_value'),
            DB::raw('sum(value * probability / 100) as weighted_value')
        )
            ->whereNotNull('expected_close_date')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $buckets->map(fn ($bucket) => [
            'month' => $bucket->month,
            'total_value' => (float) $bucket->total_value,
            'weighted_value' => (float) $bucket->weighted_value,
        ])->toArray();
    }

    public function getHistoricalWinRates(int $months = 12): array
    {
        $cutoff = Carbon::now()->subMonths($months);
        $allStages = PipelineStage::with('pipeline')->orderBy('pipeline_id')->orderBy('position')->get();

        return $allStages->map(function (PipelineStage $stage) use ($cutoff) {
            $historical = $this->getHistoricalStageWinRate($stage->name, $stage->probability, $cutoff);

            return [
                'stage' => $stage->name,
                'pipeline' => $stage->pipeline?->name,
                'configured_probability' => $stage->probability,
                'historical_win_rate' => $historical,
            ];
        })->toArray();
    }

    public function getRevenueTarget(string $period, string $periodStart, string $periodEnd, ?string $teamId): ?array
    {
        $query = RevenueTarget::query()
            ->where('period', $period)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd);

        if ($teamId) {
            $query->where('team_id', $teamId);
        } else {
            $query->whereNull('team_id');
        }

        $target = $query->first();

        if (! $target && $teamId) {
            $target = RevenueTarget::query()
                ->where('period', $period)
                ->where('period_start', $periodStart)
                ->where('period_end', $periodEnd)
                ->whereNull('team_id')
                ->first();
        }

        return $target ? $this->formatRevenueTarget($target) : null;
    }

    public function updateRevenueTarget(?string $teamId, array $validated): array
    {
        $period = $this->resolvePeriod([
            'period' => $validated['period'],
            'custom_start' => $validated['period_start'] ?? null,
            'custom_end' => $validated['period_end'] ?? null,
        ]);

        $target = RevenueTarget::query()
            ->where('period', $validated['period'])
            ->where('period_start', $period['start']->toDateString())
            ->where('period_end', $period['end']->toDateString())
            ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
            ->when(! $teamId, fn ($q) => $q->whereNull('team_id'))
            ->first();

        $target ??= RevenueTarget::create([
            'team_id' => $teamId,
            'created_by' => auth()->id(),
            'period' => $validated['period'],
            'period_start' => $period['start']->toDateString(),
            'period_end' => $period['end']->toDateString(),
            'target_revenue' => $validated['target_revenue'],
        ]);

        $target->update([
            'target_revenue' => $validated['target_revenue'],
            'created_by' => auth()->id(),
        ]);

        $target->update(['target_revenue' => $validated['target_revenue']]);

        return $this->formatRevenueTarget($target->fresh());
    }

    public function getWinLossAnalysis(array $filters = []): array
    {
        $query = Deal::query()->whereIn('stage', ['closed_won', 'closed_lost']);
        $this->applyFilters($query, $filters);

        $results = $query->leftJoin('win_loss_reasons', 'win_loss_reasons.id', '=', 'deals.win_loss_reason_id')
            ->select(
                'win_loss_reasons.type',
                DB::raw('COALESCE(win_loss_reasons.label, deals.stage) as label'),
                DB::raw('count(*) as count'),
                DB::raw('sum(value) as total_value')
            )
            ->groupBy('win_loss_reasons.type', 'label')
            ->get();

        return [
            'won' => $results->where('type', 'won')->map(fn ($r) => [
                'label' => $r->label,
                'count' => $r->count,
                'total_value' => (float) $r->total_value,
            ])->toArray(),
            'lost' => $results->where('type', 'lost')->map(fn ($r) => [
                'label' => $r->label,
                'count' => $r->count,
                'total_value' => (float) $r->total_value,
            ])->toArray(),
        ];
    }

    protected function forecastQuery(array $filters, array $period)
    {
        $query = Deal::query()
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->whereBetween('expected_close_date', [$period['start'], $period['end']]);

        $this->applyFilters($query, $filters);

        return $query;
    }

    protected function resolvePeriod(array $filters): array
    {
        $period = $filters['period'] ?? 'current_month';

        if ($period === 'current_month') {
            return ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()];
        }

        if ($period === 'next_month') {
            return ['start' => now()->addMonthNoOverflow()->startOfMonth(), 'end' => now()->addMonthNoOverflow()->endOfMonth()];
        }

        if ($period === 'current_quarter') {
            return ['start' => now()->startQuarter(), 'end' => now()->endQuarter()];
        }

        if ($period === 'next_quarter') {
            $nextQuarterStart = now()->addMonths(3)->startQuarter();

            return ['start' => $nextQuarterStart, 'end' => $nextQuarterStart->copy()->endQuarter()];
        }

        return [
            'start' => Carbon::parse($filters['custom_start'] ?? $filters['close_from'] ?? now()->startOfMonth()),
            'end' => Carbon::parse($filters['custom_end'] ?? $filters['close_to'] ?? now()->endOfMonth()),
        ];
    }

    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['owner_id']) && $filters['owner_id'] !== '') {
            $query->where('owner_id', $filters['owner_id']);
        }
        if (isset($filters['team_id']) && $filters['team_id'] !== '') {
            $query->whereHas('owner', fn ($q) => $q->whereHas('primaryTeam', fn ($team) => $team->where('team_id', $filters['team_id'])));
        }
        if (isset($filters['pipeline_id']) && $filters['pipeline_id'] !== '') {
            $query->where('pipeline_id', $filters['pipeline_id']);
        }
        if (isset($filters['pipeline_stage']) && $filters['pipeline_stage'] !== '') {
            $query->where('stage', $filters['pipeline_stage']);
        }
        if (isset($filters['region']) && $filters['region'] !== '') {
            $query->whereHas('account', fn ($q) => $q->where('billing_country', $filters['region']));
        }
        if (isset($filters['close_from']) && $filters['close_from'] !== '') {
            $query->whereDate('expected_close_date', '>=', $filters['close_from']);
        }
        if (isset($filters['close_to']) && $filters['close_to'] !== '') {
            $query->whereDate('expected_close_date', '<=', $filters['close_to']);
        }
    }

    protected function getHistoricalStageWinRate(string $stage, int $configuredProbability, Carbon $cutoff): float
    {
        if (Schema::hasTable('deal_stage_history')) {
            $history = DB::table('deal_stage_history')
                ->where('previous_stage', $stage)
                ->where('moved_at', '>=', $cutoff)
                ->whereIn('next_stage', ['closed_won', 'closed_lost'])
                ->get();

            if ($history->isNotEmpty()) {
                $won = $history->where('next_stage', 'closed_won')->count();

                return round($won / $history->count() * 100, 2);
            }
        }

        return (float) $configuredProbability;
    }

    protected function formatDealForecastRow(Deal $deal): array
    {
        return [
            'id' => $deal->id,
            'deal_name' => $deal->title,
            'account' => $deal->account?->name,
            'stage' => $deal->stage,
            'value' => (float) $deal->value,
            'weighted_value' => $deal->getWeightedValue(),
            'owner' => $deal->owner?->name,
            'expected_close_date' => $deal->expected_close_date?->toDateString(),
        ];
    }

    protected function formatRevenueTarget(RevenueTarget $target): array
    {
        return [
            'id' => $target->id,
            'team_id' => $target->team_id,
            'period' => $target->period,
            'period_start' => $target->period_start->toDateString(),
            'period_end' => $target->period_end->toDateString(),
            'target_revenue' => (float) $target->target_revenue,
            'created_by' => $target->created_by,
        ];
    }

    protected function gapStatus(float $weightedValue, float $targetValue): string
    {
        if ($targetValue <= 0) {
            return 'none';
        }

        if ($weightedValue >= $targetValue) {
            return 'green';
        }

        $gap = (($targetValue - $weightedValue) / $targetValue) * 100;

        return $gap <= 20 ? 'amber' : 'red';
    }
}

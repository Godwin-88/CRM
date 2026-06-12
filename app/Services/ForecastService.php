<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\WinLossReason;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ForecastService
{
    public function getRevenueForecast(array $filters = []): array
    {
        $query = Deal::query()->whereNotIn('stage', ['closed_won', 'closed_lost']);

        $this->applyFilters($query, $filters);

        $byStage = $query->join('pipeline_stages', function ($join) {
            $join->on('pipeline_stages.name', '=', 'deals.stage')
                ->whereColumn('pipeline_stages.pipeline_id', 'deals.pipeline_id');
        })
        ->select('deals.stage', DB::raw('sum(deals.value) as total_value'), DB::raw('sum(deals.value * pipeline_stages.probability / 100) as weighted_value'))
        ->groupBy('deals.stage')
        ->get()
        ->mapWithKeys(fn($item) => [
            $item->stage => [
                'total_value' => (float) $item->total_value,
                'weighted_value' => (float) $item->weighted_value,
            ],
        ])
        ->toArray();

        $totalUnweighted = $query->sum('value');
        $totalWeighted = $query->get()->sum(fn($d) => $d->getWeightedValue());

        return [
            'total_unweighted' => (float) $totalUnweighted,
            'total_weighted' => (float) $totalWeighted,
            'by_stage' => $byStage,
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

        return $buckets->map(fn($bucket) => [
            'month' => $bucket->month,
            'total_value' => (float) $bucket->total_value,
            'weighted_value' => (float) $bucket->weighted_value,
        ])->toArray();
    }

    public function getHistoricalWinRates(int $months = 12): array
    {
        $cutoff = Carbon::now()->subMonths($months);

        $rates = Deal::query()
            ->where('stage', 'closed_won')
            ->where('created_at', '>=', $cutoff)
            ->join('pipeline_stages', function ($join) {
                $join->on('pipeline_stages.name', '=', 'deals.stage')
                    ->whereColumn('pipeline_stages.pipeline_id', 'deals.pipeline_id');
            })
            ->select('pipeline_stages.name as stage', DB::raw('count(*) as won_count'))
            ->groupBy('pipeline_stages.name')
            ->get()
            ->keyBy('stage');

        $allStages = PipelineStage::with('pipeline')->get();

        return $allStages->map(fn($stage) => [
            'stage' => $stage->name,
            'configured_probability' => $stage->probability,
            'historical_win_rate' => $rates->get($stage->name)?->won_count ? round($rates->get($stage->name)->won_count / max(Deal::where('created_at', '>=', $cutoff)->count(), 1) * 100, 2) : 0,
        ])->toArray();
    }

    public function getWinLossAnalysis(array $filters = []): array
    {
        $query = Deal::query()->whereIn('stage', ['closed_won', 'closed_lost']);

        $this->applyFilters($query, $filters);

        $results = $query->join('win_loss_reasons', 'win_loss_reasons.id', '=', 'deals.win_loss_reason_id')
            ->select(
                'win_loss_reasons.type',
                'win_loss_reasons.label',
                DB::raw('count(*) as count'),
                DB::raw('sum(value) as total_value')
            )
            ->groupBy('win_loss_reasons.type', 'win_loss_reasons.label')
            ->get();

        return [
            'won' => $results->where('type', 'won')->map(fn($r) => [
                'label' => $r->label,
                'count' => $r->count,
                'total_value' => (float) $r->total_value,
            ])->toArray(),
            'lost' => $results->where('type', 'lost')->map(fn($r) => [
                'label' => $r->label,
                'count' => $r->count,
                'total_value' => (float) $r->total_value,
            ])->toArray(),
        ];
    }

    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }
        if (isset($filters['team_id'])) {
            $query->whereHas('owner', fn($q) => $q->where('team_id', $filters['team_id']));
        }
        if (isset($filters['pipeline_id'])) {
            $query->where('pipeline_id', $filters['pipeline_id']);
        }
        if (isset($filters['close_from'])) {
            $query->whereDate('expected_close_date', '>=', $filters['close_from']);
        }
        if (isset($filters['close_to'])) {
            $query->whereDate('expected_close_date', '<=', $filters['close_to']);
        }
    }
}
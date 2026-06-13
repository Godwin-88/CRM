<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Product;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceAnalyticsService
{
    public function getRevenueByProduct(array $filters = []): array
    {
        $query = Deal::query()->where('stage', 'closed_won');

        $this->applyDateFilters($query, $filters);
        $this->applyTeamFilters($query, $filters);

        return $query->join('products', 'products.id', '=', 'deals.product_id')
            ->select(
                'products.name',
                'products.category',
                DB::raw('sum(deals.value) as total_revenue'),
                DB::raw('count(*) as deal_count'),
                DB::raw('avg(deals.value) as avg_deal_value')
            )
            ->groupBy('products.id', 'products.name', 'products.category')
            ->orderBy('total_revenue', 'desc')
            ->get()
            ->map(fn($item) => [
                'product' => $item->name,
                'category' => $item->category,
                'total_revenue' => $item->total_revenue,
                'deal_count' => $item->deal_count,
                'avg_deal_value' => $item->avg_deal_value,
            ])
            ->toArray();
    }

    public function getRevenueByAccount(array $filters = []): array
    {
        $query = Deal::query()->where('stage', 'closed_won');

        $this->applyDateFilters($query, $filters);

        return $query->join('accounts', 'accounts.id', '=', 'deals.account_id')
            ->select(
                'accounts.name',
                DB::raw('sum(deals.value) as total_revenue'),
                DB::raw('count(*) as deal_count')
            )
            ->groupBy('accounts.id', 'accounts.name')
            ->orderBy('total_revenue', 'desc')
            ->get()
            ->map(fn($item) => [
                'account' => $item->name,
                'total_revenue' => $item->total_revenue,
                'deal_count' => $item->deal_count,
            ])
            ->toArray();
    }

    public function getRevenueTrend(array $filters = []): array
    {
        $query = Deal::query()->where('stage', 'closed_won');

        $this->applyTeamFilters($query, $filters);

        return $query->select(
            DB::raw("TO_CHAR(updated_at, 'YYYY-MM') as month"),
            DB::raw('sum(value) as revenue')
        )
        ->groupBy('month')
        ->orderBy('month')
        ->get()
        ->map(fn($item) => [
            'month' => $item->month,
            'revenue' => $item->revenue,
        ])
        ->toArray();
    }

    public function getAccountsReceivableAging(array $filters = []): array
    {
        $query = Deal::query()->where('stage', 'closed_won');

        $this->applyDateFilters($query, $filters);

        $now = Carbon::now();
        $aging = [
            'current' => ['min_days' => 0, 'max_days' => 30, 'value' => 0, 'count' => 0],
            '31_60' => ['min_days' => 31, 'max_days' => 60, 'value' => 0, 'count' => 0],
            '61_90' => ['min_days' => 61, 'max_days' => 90, 'value' => 0, 'count' => 0],
            'over_90' => ['min_days' => 91, 'max_days' => null, 'value' => 0, 'count' => 0],
        ];

        $deals = $query->whereNotNull('expected_close_date')->get();

        foreach ($deals as $deal) {
            $days = Carbon::parse($deal->expected_close_date)->diffInDays($now);
            
            if ($days <= 30) {
                $aging['current']['value'] += $deal->value;
                $aging['current']['count']++;
            } elseif ($days <= 60) {
                $aging['31_60']['value'] += $deal->value;
                $aging['31_60']['count']++;
            } elseif ($days <= 90) {
                $aging['61_90']['value'] += $deal->value;
                $aging['61_90']['count']++;
            } else {
                $aging['over_90']['value'] += $deal->value;
                $aging['over_90']['count']++;
            }
        }

        unset($aging['current']['min_days'], $aging['current']['max_days']);
        unset($aging['31_60']['min_days'], $aging['31_60']['max_days']);
        unset($aging['61_90']['min_days'], $aging['61_90']['max_days']);
        unset($aging['over_90']['min_days'], $aging['over_90']['max_days']);

        return $aging;
    }

    public function getRevenueAllocation(array $filters = []): array
    {
        $query = Deal::query()->where('stage', 'closed_won');

        $this->applyDateFilters($query, $filters);

        return [
            'by_pipeline' => $query->join('pipelines', 'pipelines.id', '=', 'deals.pipeline_id')
                ->select('pipelines.name', DB::raw('sum(deals.value) as value'))
                ->groupBy('pipelines.id', 'pipelines.name')
                ->get()
                ->map(fn($item) => ['name' => $item->name, 'value' => $item->value])
                ->toArray(),

            'by_contact_type' => $query->join('contacts', 'contacts.id', '=', 'deals.contact_id')
                ->select('contacts.type', DB::raw('sum(deals.value) as value'))
                ->groupBy('contacts.type')
                ->get()
                ->map(fn($item) => ['type' => $item->type, 'value' => $item->value])
                ->toArray(),

            'by_region' => $query->join('accounts', 'accounts.id', '=', 'deals.account_id')
                ->select('accounts.billing_country', DB::raw('sum(deals.value) as value'))
                ->whereNotNull('accounts.billing_country')
                ->groupBy('accounts.billing_country')
                ->get()
                ->map(fn($item) => ['country' => $item->billing_country, 'value' => $item->value])
                ->toArray(),
        ];
    }

    protected function applyDateFilters($query, array $filters): void
    {
        if (isset($filters['date_from'])) {
            $query->where('updated_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('updated_at', '<=', $filters['date_to']);
        }
    }

    protected function applyTeamFilters($query, array $filters): void
    {
        if (isset($filters['team_id'])) {
            $query->whereHas('owner', fn($q) => $q->where('team_id', $filters['team_id']));
        }
        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }
    }
}
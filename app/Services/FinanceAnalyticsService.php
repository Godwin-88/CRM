<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceAnalyticsService
{
    public function getRevenueByProduct(array $filters = []): array
    {
        $query = Deal::query()->where('stage', 'closed_won');

        $this->applyFilters($query, $filters);

        return $query->leftJoin('products', 'products.id', '=', 'deals.product_id')
            ->select(
                DB::raw('COALESCE(products.name, \'No product\') as product'),
                DB::raw('COALESCE(products.category, \'Uncategorized\') as category'),
                DB::raw('sum(deals.value) as total_revenue'),
                DB::raw('count(*) as deal_count'),
                DB::raw('avg(deals.value) as avg_deal_value')
            )
            ->groupBy('product', 'category')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(fn ($item) => [
                'product' => $item->product,
                'category' => $item->category,
                'total_revenue' => (float) $item->total_revenue,
                'deal_count' => (int) $item->deal_count,
                'avg_deal_value' => (float) $item->avg_deal_value,
            ])
            ->toArray();
    }

    public function getRevenueByAccount(array $filters = []): array
    {
        $query = Deal::query()->where('stage', 'closed_won');

        $this->applyFilters($query, $filters);

        if (isset($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        return $query->join('accounts', 'accounts.id', '=', 'deals.account_id')
            ->select(
                'accounts.name',
                DB::raw('sum(deals.value) as total_revenue'),
                DB::raw('count(*) as deal_count')
            )
            ->groupBy('accounts.id', 'accounts.name')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(fn ($item) => [
                'account' => $item->name,
                'total_revenue' => (float) $item->total_revenue,
                'deal_count' => (int) $item->deal_count,
            ])
            ->toArray();
    }

    public function getRevenueByAgent(array $filters = []): array
    {
        $query = Deal::query()->where('stage', 'closed_won');

        $this->applyFilters($query, $filters);

        return $query->join('users', 'users.id', '=', 'deals.owner_id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('sum(deals.value) as total_revenue'),
                DB::raw('count(*) as deal_count')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(fn ($item) => [
                'agent_id' => $item->id,
                'agent' => $item->name,
                'total_revenue' => (float) $item->total_revenue,
                'deal_count' => (int) $item->deal_count,
            ])
            ->toArray();
    }

    public function getRevenueTrend(array $filters = []): array
    {
        $query = Deal::query()->where('stage', 'closed_won');

        $this->applyFilters($query, $filters);

        return $query->select(
            DB::raw("TO_CHAR(updated_at, 'YYYY-MM') as month"),
            DB::raw('sum(value) as revenue')
        )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($item) => [
                'month' => $item->month,
                'revenue' => (float) $item->revenue,
            ])
            ->toArray();
    }

    public function getAccountsReceivableAging(array $filters = []): array
    {
        $query = Invoice::query()
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->where('total', '>', 0);

        if (isset($filters['date_from'])) {
            $query->where('due_date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('due_date', '<=', $filters['date_to']);
        }
        if (isset($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        $this->applyTeamFilters($query, $filters);

        $aging = [
            'current' => ['label' => 'Current (0-30)', 'value' => 0.0, 'count' => 0, 'invoices' => []],
            '31_60' => ['label' => '31-60 days', 'value' => 0.0, 'count' => 0, 'invoices' => []],
            '61_90' => ['label' => '61-90 days', 'value' => 0.0, 'count' => 0, 'invoices' => []],
            'over_90' => ['label' => 'Over 90 days', 'value' => 0.0, 'count' => 0, 'invoices' => []],
        ];

        $invoices = $query->with(['account', 'contact.owner', 'deals.owner'])->get();
        $now = Carbon::now();

        foreach ($invoices as $invoice) {
            $daysPastDue = max(0, $now->diffInDays($invoice->due_date, false));
            $bucket = $this->bucketForDays($daysPastDue);
            $outstanding = $invoice->getOutstandingBalanceAttribute();
            $aging[$bucket]['value'] += $outstanding;
            $aging[$bucket]['count']++;
            $aging[$bucket]['invoices'][] = [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'account' => $invoice->account?->name,
                'value' => (float) $outstanding,
                'due_date' => $invoice->due_date?->toDateString(),
                'assigned_agent' => $this->invoiceOwnerName($invoice),
            ];
        }

        foreach ($aging as $key => $bucket) {
            $aging[$key]['value'] = round($bucket['value'], 2);
        }

        return $aging;
    }

    public function getRevenueAllocation(array $filters = []): array
    {
        $query = Deal::query()->where('stage', 'closed_won');

        $this->applyFilters($query, $filters);

        return [
            'by_pipeline' => $query->join('pipelines', 'pipelines.id', '=', 'deals.pipeline_id')
                ->select('pipelines.name', DB::raw('sum(deals.value) as value'))
                ->groupBy('pipelines.id', 'pipelines.name')
                ->get()
                ->map(fn ($item) => ['name' => $item->name, 'value' => (float) $item->value])
                ->toArray(),

            'by_contact_type' => $query->join('contacts', 'contacts.id', '=', 'deals.contact_id')
                ->select('contacts.type', DB::raw('sum(deals.value) as value'))
                ->groupBy('contacts.type')
                ->get()
                ->map(fn ($item) => ['type' => $item->type, 'value' => (float) $item->value])
                ->toArray(),

            'by_region' => $query->join('accounts', 'accounts.id', '=', 'deals.account_id')
                ->select('accounts.billing_country', DB::raw('sum(deals.value) as value'))
                ->whereNotNull('accounts.billing_country')
                ->groupBy('accounts.billing_country')
                ->get()
                ->map(fn ($item) => ['country' => $item->billing_country, 'value' => (float) $item->value])
                ->toArray(),
        ];
    }

    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['date_from'])) {
            $query->where('updated_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('updated_at', '<=', $filters['date_to']);
        }
        if (isset($filters['pipeline_id'])) {
            $query->where('pipeline_id', $filters['pipeline_id']);
        }
        if (isset($filters['product_category'])) {
            $query->whereHas('product', fn ($q) => $q->where('category', $filters['product_category']));
        }
        if (isset($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
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

    protected function bucketForDays(int $daysPastDue): string
    {
        return match (true) {
            $daysPastDue <= 30 => 'current',
            $daysPastDue <= 60 => '31_60',
            $daysPastDue <= 90 => '61_90',
            default => 'over_90',
        };
    }

    protected function invoiceOwnerName(Invoice $invoice): ?string
    {
        $dealOwner = $invoice->deals->firstWhere(fn ($deal) => $deal->owner)?->owner?->name;

        if ($dealOwner) {
            return $dealOwner;
        }

        return $invoice->contact?->owner?->name;
    }
}

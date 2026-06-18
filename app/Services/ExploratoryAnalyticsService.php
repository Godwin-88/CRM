<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Deal;
use App\Models\Interaction;
use App\Models\ReportDefinition;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class ExploratoryAnalyticsService
{
    protected array $entityModels = [
        'contacts' => Contact::class,
        'accounts' => Account::class,
        'deals' => Deal::class,
        'interactions' => Interaction::class,
        'tickets' => Ticket::class,
        'campaigns' => Campaign::class,
        'contracts' => Contract::class,
    ];

    protected array $allowedAggregates = [
        'count' => 'count',
        'sum' => 'sum',
        'avg' => 'avg',
        'min' => 'min',
        'max' => 'max',
    ];

    public function runReport(ReportDefinition $report): array
    {
        $model = $this->entityModels[$report->entity_type] ?? Contact::class;
        $query = $model::query();

        if ($report->filters) {
            $this->applyFilters($query, $report->filters);
        }

        if ($report->sort_field) {
            $query->orderBy($this->sanitizeIdentifier($report->sort_field), $report->sort_direction ?? 'asc');
        }

        $estimatedRows = $query->count();
        $background = $estimatedRows > 10000;

        if ($report->group_by) {
            $groupBy = $this->sanitizeIdentifier($report->group_by);
            $rows = $this->getGroupedData($query, $report, $groupBy);
        } else {
            $columns = $report->fields && is_array($report->fields) ? array_values($report->fields) : ['*'];
            $rows = $query->limit($background ? 10000 : 10000)->get($columns)->map(fn ($row) => $row instanceof \Illuminate\Database\Eloquent\Model ? $row->toArray() : (array) $row)->toArray();
        }

        return [
            'rows' => $rows,
            'row_count' => count($rows),
            'background' => $background,
        ];
    }

    public function getExploratoryData(string $entityType, array $filters = [], array $fields = []): array
    {
        $model = $this->entityModels[$entityType] ?? Contact::class;
        $query = $model::query();

        $this->applyFilters($query, $filters);

        $columns = $fields ?: ['*'];

        return $query->limit(10000)->get($columns)->map(fn ($row) => $row instanceof \Illuminate\Database\Eloquent\Model ? $row->toArray() : (array) $row)->toArray();
    }

    public function getGroupedData($query, ReportDefinition $report, ?string $groupBy = null): array
    {
        $groupBy = $this->sanitizeIdentifier($groupBy ?? $report->group_by);
        $aggregateField = $this->sanitizeIdentifier($report->fields['aggregate_field'] ?? 'id');
        $aggregateFn = $report->fields['aggregate_fn'] ?? 'count';
        $aggregateFn = $this->allowedAggregates[$aggregateFn] ?? 'count';

        if ($aggregateFn === 'count') {
            $query->select($groupBy, DB::raw('count(*) as value'));
        } else {
            $query->select($groupBy, DB::raw("{$aggregateFn}({$aggregateField}) as value"));
        }

        return $query->groupBy($groupBy)
            ->orderBy('value', 'desc')
            ->get()
            ->map(fn ($row) => [
                'group' => $row->group,
                'value' => (float) $row->value,
            ])
            ->toArray();
    }

    protected function applyFilters($query, array $filters): void
    {
        foreach ($filters as $field => $condition) {
            if (! is_array($condition)) {
                continue;
            }

            foreach ($condition as $operator => $value) {
                $column = $this->sanitizeIdentifier(is_int($operator) ? $field : $field);

                match ($operator) {
                    'gte', '>=' => $query->where($column, '>=', $value),
                    'lte', '<=' => $query->where($column, '<=', $value),
                    'gt', '>' => $query->where($column, '>', $value),
                    'lt', '<' => $query->where($column, '<', $value),
                    'equals', '=' => $query->where($column, $value),
                    'contains' => $query->where($column, 'like', "%{$value}%"),
                    'in' => $query->whereIn($column, (array) $value),
                    'not_in' => $query->whereNotIn($column, (array) $value),
                    default => $query->where($column, $value),
                };
            }
        }
    }

    protected function sanitizeIdentifier(string $identifier): string
    {
        return preg_replace('/[^A-Za-z0-9_]/', '', $identifier) ?: 'id';
    }
}

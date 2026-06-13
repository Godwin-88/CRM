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

    public function runReport(ReportDefinition $report): array
    {
        $model = $this->entityModels[$report->entity_type] ?? Contact::class;

        $query = $model::query();

        if ($report->filters) {
            $this->applyFilters($query, $report->filters);
        }

        if ($report->sort_field) {
            $query->orderBy($report->sort_field, $report->sort_direction ?? 'asc');
        }

        if ($report->group_by) {
            return $this->getGroupedData($query, $report);
        }

        $columns = $report->fields ?? ['*'];

        return $query->get($columns)->toArray();
    }

    public function getExploratoryData(string $entityType, array $filters = [], array $fields = []): array
    {
        $model = $this->entityModels[$entityType] ?? Contact::class;

        $query = $model::query();

        $this->applyFilters($query, $filters);

        $data = $query->get($fields ?: ['*'])->toArray();

        return $data;
    }

    public function getGroupedData($query, ReportDefinition $report): array
    {
        $groupBy = $report->group_by;
        $aggregateField = $report->fields['aggregate'] ?? 'id';
        $aggregateFn = $report->fields['aggregate_fn'] ?? 'count';

        $results = $query->select(
            $groupBy.' as group',
            DB::raw("{$aggregateFn}({$aggregateField}) as value")
        )
            ->groupBy($groupBy)
            ->get();

        return $results->map(fn ($row) => [
            'group' => $row->group,
            'value' => $row->value,
        ])->toArray();
    }

    protected function applyFilters($query, array $filters): void
    {
        foreach ($filters as $field => $condition) {
            if (! is_array($condition)) {
                continue;
            }

            foreach ($condition as $operator => $value) {
                $column = is_int($operator) ? $field : "{$field} {$operator}";

                match ($operator) {
                    'gte', '>=' => $query->where($field, '>=', $value),
                    'lte', '<=' => $query->where($field, '<=', $value),
                    'gt', '>' => $query->where($field, '>', $value),
                    'lt', '<' => $query->where($field, '<', $value),
                    'equals', '=' => $query->where($field, $value),
                    'contains' => $query->where($field, 'like', "%{$value}%"),
                    'in' => $query->whereIn($field, (array) $value),
                    'not_in' => $query->whereNotIn($field, (array) $value),
                    default => $query->where($field, $value),
                };
            }
        }
    }
}

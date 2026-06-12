<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class SegmentService
{
    /**
     * Apply rule-based criteria to a query with AND/OR logic.
     *
     * Criteria format:
     * [
     *   'join_operator' => 'and|or',
     *   'rules' => [
     *     ['field' => 'clv_score', 'operator' => '>=', 'value' => 500],
     *     ['field' => 'country', 'operator' => '=', 'value' => 'USA']
     *   ]
     * ]
     */
    public function applyCriteria(Builder $query, array $criteria): Builder
    {
        $rules = $criteria['rules'] ?? $criteria;
        $joinOperator = $criteria['join_operator'] ?? 'and';

        return $query->where(function (Builder $q) use ($rules, $joinOperator) {
            $first = true;
            foreach ($rules as $rule) {
                if ($first) {
                    $this->applyRule($q, $rule);
                    $first = false;
                } else {
                    if ($joinOperator === 'or') {
                        $q->orWhere(function (Builder $sub) use ($rule) {
                            $this->applyRule($sub, $rule);
                        });
                    } else {
                        $this->applyRule($q, $rule);
                    }
                }
            }
        });
    }

    /**
     * Apply a single filter rule to a query.
     */
    protected function applyRule(Builder $query, array $rule): void
    {
        $field = $rule['field'] ?? '';
        $operator = $rule['operator'] ?? '=';
        $value = $rule['value'] ?? '';

        // Handle special dimensions that require subqueries or joins
        switch ($field) {
            case 'last_interaction_date':
                $query->where(function ($q) use ($operator, $value) {
                    $q->whereHas('interactions', function ($sub) use ($operator, $value) {
                        $sub->where('created_at', $operator, $value);
                    });
                });
                return;

            case 'custom_field':
                $customFieldKey = $rule['custom_field_key'] ?? '';
                $query->whereHas('customFieldValues', function ($q) use ($customFieldKey, $operator, $value) {
                    $q->where('field_key', $customFieldKey);
                    $q->where('value', $operator, $value);
                });
                return;

            case 'created_date':
                $query->whereDate('created_at', $operator, $value);
                return;

            case 'owner_name':
                $query->whereHas('owner', function ($q) use ($operator, $value) {
                    if ($operator === 'contains') {
                        $q->where('name', 'like', '%' . $value . '%');
                    } else {
                        $q->where('name', $operator, $value);
                    }
                });
                return;
        }

        // Standard field matching
        match ($operator) {
            'contains' => $query->where($field, 'like', '%' . $value . '%'),
            'not_contains' => $query->where($field, 'not like', '%' . $value . '%'),
            'starts_with' => $query->where($field, 'like', $value . '%'),
            'ends_with' => $query->where($field, 'like', '%' . $value),
            'in' => $query->whereIn($field, is_array($value) ? $value : explode(',', $value)),
            'not_in' => $query->whereNotIn($field, is_array($value) ? $value : explode(',', $value)),
            'between' => $query->whereBetween($field, is_array($value) ? $value : explode(',', $value)),
            'is_null' => $query->whereNull($field),
            'is_not_null' => $query->whereNotNull($field),
            default => $query->where($field, $operator, $value),
        };
    }

    /**
     * Get preview of matching contacts: count + sample records.
     */
    public function getPreview(array $criteria, int $sampleSize = 10): array
    {
        $query = $this->applyCriteria(Contact::query(), $criteria);
        $count = $query->count();
        $sample = $query->take($sampleSize)->get(['id', 'first_name', 'last_name', 'email', 'type']);

        return [
            'count' => $count,
            'sample' => $sample,
        ];
    }

    /**
     * Get cached contact count for a segment.
     */
    public function getCachedCount(Segment $segment): int
    {
        $cacheKey = "segment:{$segment->id}:count";
        return Cache::remember($cacheKey, 3600, function () use ($segment) {
            $criteria = $segment->criteria ?: ['rules' => []];
            return $this->applyCriteria(Contact::query(), $criteria)->count();
        });
    }

    /**
     * Refresh segment count cache.
     */
    public function refreshCount(Segment $segment): void
    {
        $count = $this->getCachedCount($segment);
        $segment->update([
            'contact_count' => $count,
            'contact_count_cached_at' => now(),
        ]);
    }

    /**
     * Refresh counts for all segments.
     */
    public function refreshAllCounts(): void
    {
        Segment::chunk(100, function ($segments) {
            foreach ($segments as $segment) {
                $this->refreshCount($segment);
            }
        });
    }

    /**
     * Evaluate which contacts match a segment at query time.
     */
    public function getMatchingContacts(Segment $segment)
    {
        $criteria = $segment->criteria ?: ['rules' => []];
        return $this->applyCriteria(Contact::query(), $criteria);
    }
}
<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ScoringRule;
use Illuminate\Support\Facades\Cache;

class ScoringService
{
    /**
     * Calculate the score for a contact based on enabled scoring rules.
     */
    public function calculateScore(Contact $contact): int
    {
        $rules = ScoringRule::where('entity_type', 'contact')
            ->where('is_enabled', true)
            ->get();

        $totalScore = 0;

        foreach ($rules as $rule) {
            if ($this->ruleMatches($contact, $rule)) {
                $totalScore += $rule->points;
            }
        }

        return $totalScore;
    }

    /**
     * Check if a contact matches a scoring rule condition.
     */
    protected function ruleMatches(Contact $contact, ScoringRule $rule): bool
    {
        $fieldValue = $contact->{$rule->field} ?? null;

        return match ($rule->operator) {
            '=' => $fieldValue == $rule->value,
            '!=' => $fieldValue != $rule->value,
            '>' => $fieldValue > $rule->value,
            '>=' => $fieldValue >= $rule->value,
            '<' => $fieldValue < $rule->value,
            '<=' => $fieldValue <= $rule->value,
            'contains' => is_string($fieldValue) && str_contains($fieldValue, $rule->value),
            'in' => in_array($fieldValue, explode(',', $rule->value)),
            'between' => $this->isBetween($fieldValue, $rule->value),
            default => false,
        };
    }

    /**
     * Check if a value is between a range.
     */
    protected function isBetween($value, string $range): bool
    {
        $parts = explode(',', $range);
        if (count($parts) !== 2) {
            return false;
        }

        $min = trim($parts[0]);
        $max = trim($parts[1]);

        // Handle dates
        if (str_contains($min, '-') && str_contains($max, '-')) {
            $date = is_string($value) ? strtotime($value) : $value;
            $start = strtotime($min);
            $end = strtotime($max);

            return $date >= $start && $date <= $end;
        }

        return $value >= (float) $min && $value <= (float) $max;
    }

    /**
     * Update the score for a single contact and persist it.
     */
    public function updateContactScore(Contact $contact): int
    {
        $score = $this->calculateScore($contact);
        $contact->update(['score' => $score]);

        return $score;
    }

    /**
     * Recalculate scores for all contacts (batch job).
     */
    public function recalculateAll(): void
    {
        Contact::chunk(100, function ($contacts) {
            foreach ($contacts as $contact) {
                $this->updateContactScore($contact);
            }
        });
    }

    /**
     * Get score thresholds for colour coding.
     * Configurable via cache.
     */
    public function getThresholds(): array
    {
        return Cache::remember('scoring:thresholds', 86400, function () {
            return [
                'green_min' => 50,
                'amber_min' => 20,
                'red_max' => 19,
            ];
        });
    }

    /**
     * Get colour code for a score.
     */
    public function getScoreColor(int $score): string
    {
        $thresholds = $this->getThresholds();

        if ($score >= $thresholds['green_min']) {
            return 'green';
        }
        if ($score >= $thresholds['amber_min']) {
            return 'amber';
        }

        return 'red';
    }
}

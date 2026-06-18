<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\ScoringRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PredictiveScoringService
{
    protected array $defaultSignalWeights = [
        'days_in_stage' => 25,
        'recent_interactions' => 20,
        'demo_trial_completed' => 20,
        'deal_value' => 15,
        'contact_engagement' => 10,
        'days_to_close' => 10,
    ];

    public function calculateDealScore(Deal $deal): array
    {
        $signals = $this->evaluateSignals($deal);
        $weights = $this->getScoringWeights();
        $totalScore = 0;

        foreach ($signals as $signal => $value) {
            $totalScore += $value * (($weights[$signal] ?? 0) / 100);
        }

        $totalScore = (int) round(min(100, max(0, $totalScore)));

        return [
            'score' => $totalScore,
            'label' => $this->getScoreLabel($totalScore),
            'signals' => $signals,
        ];
    }

    protected function evaluateSignals(Deal $deal): array
    {
        $avgDaysInStage = $this->getAverageDaysInStage($deal->stage);
        $daysInCurrentStage = $this->getDaysInCurrentStage($deal);
        $daysInStageScore = $this->calculateDaysInStageScore($daysInCurrentStage, $avgDaysInStage);

        $interactionCount = $deal->contact
            ? $deal->contact->interactions()->where('created_at', '>=', Carbon::now()->subDays(14))->count()
            : 0;
        $recentInteractionsScore = min($interactionCount * 10, 100);

        $demoTrialScore = $deal->demoTrials()->whereNotNull('completed_at')->exists() ? 100 : 0;

        $avgDealValue = Deal::where('pipeline_id', $deal->pipeline_id)->avg('value') ?: Deal::avg('value') ?: 1;
        $dealValueScore = $deal->value > $avgDealValue
            ? min(100, ($deal->value / $avgDealValue) * 50)
            : ($deal->value / $avgDealValue) * 100;

        $contactEngagementScore = $deal->contact?->score ?? 0;

        $daysToClose = $deal->expected_close_date
            ? Carbon::parse($deal->expected_close_date)->diffInDays(Carbon::now())
            : 365;
        $daysToCloseScore = $daysToClose > 0
            ? min(100, 365 / $daysToClose * 100)
            : 100;

        return [
            'days_in_stage' => $daysInStageScore,
            'recent_interactions' => min($recentInteractionsScore, 100),
            'demo_trial_completed' => $demoTrialScore,
            'deal_value' => min($dealValueScore, 100),
            'contact_engagement' => min($contactEngagementScore, 100),
            'days_to_close' => min($daysToCloseScore, 100),
        ];
    }

    protected function getAverageDaysInStage(string $stage): float
    {
        if (is_countable($stage)) {
            return 30;
        }

        if (DB::getSchemaBuilder()->hasTable('deal_stage_history')) {
            $avg = DB::table('deal_stage_history')
                ->where('previous_stage', $stage)
                ->whereNotNull('days_in_stage')
                ->avg('days_in_stage');

            if ($avg) {
                return (float) $avg;
            }
        }

        return 30;
    }

    protected function getDaysInCurrentStage(Deal $deal): int
    {
        if (DB::getSchemaBuilder()->hasTable('deal_stage_history')) {
            $lastMove = DB::table('deal_stage_history')
                ->where('deal_id', $deal->id)
                ->orderByDesc('moved_at')
                ->value('moved_at');

            if ($lastMove) {
                return max(0, Carbon::parse($lastMove)->diffInDays(now()));
            }
        }

        return $deal->created_at ? max(0, Carbon::parse($deal->created_at)->diffInDays(now())) : 0;
    }

    protected function calculateDaysInStageScore(int $daysInProgress, float $avgDays): int
    {
        if ($avgDays == 0) {
            return 50;
        }

        $ratio = $daysInProgress / $avgDays;

        return match (true) {
            $ratio >= 2 => 75,
            $ratio >= 1 => 50,
            $ratio >= 0.5 => 25,
            default => 10,
        };
    }

    public function recalculateAllOpenDeals(): void
    {
        Deal::whereNotIn('stage', ['closed_won', 'closed_lost'])->each(function (Deal $deal) {
            $scoring = $this->calculateDealScore($deal);
            $deal->update([
                'predicted_score' => $scoring['score'],
                'score_last_calculated_at' => now(),
            ]);
        });
    }

    public function getScoreHistory(Deal $deal): array
    {
        return [
            'current_score' => $deal->predicted_score,
            'manual_score' => $deal->manual_score,
            'last_calculated' => $deal->score_last_calculated_at,
        ];
    }

    public function setManualScore(Deal $deal, int $score, string $note): void
    {
        $deal->update([
            'manual_score' => $score,
            'score_override_note' => $note,
        ]);
    }

    public function clearManualScore(Deal $deal): void
    {
        $deal->update([
            'manual_score' => null,
            'score_override_note' => null,
        ]);
    }

    public function getScoringWeights(): array
    {
        $rules = ScoringRule::where('entity_type', 'deal_score')
            ->where('is_enabled', true)
            ->pluck('points', 'field');

        return collect($this->defaultSignalWeights)
            ->map(fn ($weight, $signal) => (int) ($rules->get($signal) ?? $weight))
            ->toArray();
    }

    public function updateWeights(array $weights): void
    {
        foreach ($weights as $signal => $weight) {
            if (! isset($this->defaultSignalWeights[$signal])) {
                continue;
            }

            ScoringRule::updateOrCreate(
                ['entity_type' => 'deal_score', 'field' => $signal, 'operator' => 'weight'],
                [
                    'name' => "Deal score: {$signal}",
                    'value' => $weight,
                    'points' => (int) $weight,
                    'is_enabled' => true,
                ]
            );
        }
    }

    protected function getScoreLabel(int $score): string
    {
        return match (true) {
            $score <= 25 => 'cold',
            $score <= 50 => 'warm',
            $score <= 75 => 'hot',
            default => 'very_hot',
        };
    }
}

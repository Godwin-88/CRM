<?php

namespace App\Services;

use App\Models\Deal;
use Carbon\Carbon;

class PredictiveScoringService
{
    protected array $signalWeights = [
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
        $totalScore = 0;

        foreach ($signals as $signal => $value) {
            $weight = $this->signalWeights[$signal] ?? 0;
            $totalScore += $value * ($weight / 100);
        }

        $totalScore = (int) round($totalScore);

        $label = match (true) {
            $totalScore <= 25 => 'cold',
            $totalScore <= 50 => 'warm',
            $totalScore <= 75 => 'hot',
            default => 'very_hot',
        };

        return [
            'score' => $totalScore,
            'label' => $label,
            'signals' => $signals,
        ];
    }

    protected function evaluateSignals(Deal $deal): array
    {
        $avgDaysInStage = $this->getAverageDaysInStage($deal->stage);
        $daysInCurrentStage = $this->getDaysInCurrentStage($deal);
        $daysInStageScore = $this->calculateDaysInStageScore($daysInCurrentStage, $avgDaysInStage);

        $interactionCount = $deal->contact->interactions()
            ->where('created_at', '>=', Carbon::now()->subDays(14))
            ->count();
        $recentInteractionsScore = min($interactionCount * 10, 100);

        $demoTrialScore = $deal->demoTrials()->exists() ? 100 : 0;

        $avgDealValue = Deal::avg('value') ?: 1;
        $dealValueScore = $deal->value > $avgDealValue
            ? min(100, ($deal->value / $avgDealValue) * 50)
            : ($deal->value / $avgDealValue) * 100;

        $contactEngagementScore = $deal->contact->score ?? 0;

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
            'contact_engagement' => $contactEngagementScore,
            'days_to_close' => min($daysToCloseScore, 100),
        ];
    }

    protected function getAverageDaysInStage(string $stage): float
    {
        $closedDeals = Deal::where('stage', $stage)
            ->whereNotIn('final_stage', ['closed_won', 'closed_lost'])
            ->get();

        if ($closedDeals->isEmpty()) {
            return 30;
        }

        return $closedDeals->avg(fn ($d) => Carbon::parse($d->created_at)->diffInDays(\Carbon::parse($d->updated_at ?: now())));
    }

    protected function getDaysInCurrentStage(Deal $deal): int
    {
        return Carbon::parse($deal->created_at)->diffInDays(now());
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
        Deal::whereNotIn('stage', ['closed_won', 'closed_lost'])->each(function ($deal) {
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
        return $this->signalWeights;
    }

    public function updateWeights(array $weights): void
    {
        $this->signalWeights = array_merge($this->signalWeights, $weights);
    }
}

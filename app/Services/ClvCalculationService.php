<?php

namespace App\Services;

use App\Models\ClvCalculation;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Interaction;
use App\Models\SurveyResponse;
use App\Models\Ticket;

class ClvCalculationService
{
    public function calculateForContact(int $contactId): ClvCalculation
    {
        $contact = Contact::findOrFail($contactId);

        $historicalClv = $this->calculateHistoricalClv($contactId);
        $predictedLtv = $this->calculatePredictedLtv($contact);
        $churnRiskScore = $this->calculateChurnRisk($contactId);
        $churnRiskBand = $this->getChurnRiskBand($churnRiskScore);

        return ClvCalculation::updateOrCreate(
            ['contact_id' => $contactId],
            [
                'historical_clv' => $historicalClv,
                'predicted_ltv' => $predictedLtv,
                'churn_risk_score' => $churnRiskScore,
                'churn_risk_band' => $churnRiskBand,
                'calculated_at' => now(),
            ]
        );
    }

    public function calculateHistoricalClv(int $contactId): float
    {
        return (float) Deal::where('contact_id', $contactId)
            ->where('stage', 'closed_won')
            ->sum('value');
    }

    public function calculatePredictedLtv(Contact $contact): float
    {
        $closedDeals = Deal::where('contact_id', $contact->id)
            ->where('stage', 'closed_won')
            ->get();

        $avgDealValue = $closedDeals->avg('value') ?? 0;
        $dealFrequency = $this->estimateDealFrequency($contact->id);
        $lifespanYears = $this->estimateLifespan($contact);

        if ($lifespanYears == 0) {
            return 0;
        }

        return $avgDealValue * $dealFrequency * $lifespanYears;
    }

    public function calculateChurnRisk(int $contactId): int
    {
        $score = 0;
        $contact = Contact::findOrFail($contactId);

        $lastInteraction = Interaction::where('contact_id', $contactId)
            ->orderByDesc('created_at')
            ->first();

        $daysSinceInteraction = $lastInteraction
            ? $lastInteraction->created_at->diffInDays(now())
            : 365;

        if ($daysSinceInteraction > 90) {
            $score += 30;
        } elseif ($daysSinceInteraction > 60) {
            $score += 20;
        } elseif ($daysSinceInteraction > 30) {
            $score += 10;
        }

        $openTickets = Ticket::where('contact_id', $contactId)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();
        $score += min($openTickets * 5, 20);

        $lastNps = SurveyResponse::where('contact_id', $contactId)
            ->orderByDesc('responded_at')
            ->first();

        if ($lastNps) {
            if ($lastNps->score <= 6) {
                $score += 25;
            } elseif ($lastNps->score <= 8) {
                $score += 10;
            }
        }

        $lastDeal = Deal::where('contact_id', $contactId)
            ->where('stage', 'closed_won')
            ->orderByDesc('created_at')
            ->first();

        if ($lastDeal && $lastDeal->created_at->diffInMonths(now()) > 12) {
            $score += 15;
        }

        return min($score, 100);
    }

    public function getChurnRiskBand(int $score): string
    {
        if ($score >= 70) {
            return 'high';
        }
        if ($score >= 40) {
            return 'medium';
        }

        return 'low';
    }

    public function recalculateAll(): void
    {
        Contact::chunk(100, function ($contacts) {
            foreach ($contacts as $contact) {
                $this->calculateForContact($contact->id);
            }
        });
    }

    private function estimateDealFrequency(int $contactId): float
    {
        $closedDeals = Deal::where('contact_id', $contactId)
            ->where('stage', 'closed_won')
            ->orderBy('created_at')
            ->get();

        if ($closedDeals->count() < 2) {
            return 1.0;
        }

        $firstDeal = $closedDeals->first()->created_at;
        $lastDeal = $closedDeals->last()->created_at;
        $monthsActive = max($firstDeal->diffInMonths($lastDeal), 1);

        return $closedDeals->count() / $monthsActive;
    }

    private function estimateLifespan(Contact $contact): float
    {
        return match ($contact->type) {
            'customer' => 3.0,
            'partner' => 5.0,
            'prospect' => 0.5,
            'lead' => 0.3,
            default => 2.0,
        };
    }
}

<?php

namespace App\Services;

use App\Jobs\SendTierChangeNotification;
use App\Models\LoyaltyEnrollment;
use App\Models\PointsLedger;
use Illuminate\Contracts\Pagination\Paginator;

class PointsCalculationService
{
    public function creditPoints(LoyaltyEnrollment $enrollment, int $points, ?string $description = null, ?string $triggeredByEvent = null): PointsLedger
    {
        $lastLedger = PointsLedger::where('enrollment_id', $enrollment->id)
            ->orderByDesc('transaction_date')
            ->first();

        $runningBalance = ($lastLedger?->running_balance ?? 0) + $points;

        $ledger = PointsLedger::create([
            'enrollment_id' => $enrollment->id,
            'contact_id' => $enrollment->contact_id,
            'program_id' => $enrollment->program_id,
            'type' => 'credit',
            'points_amount' => $points,
            'running_balance' => $runningBalance,
            'description' => $description,
            'triggered_by_event' => $triggeredByEvent,
            'transaction_date' => now(),
        ]);

        $this->evaluateTierChange($enrollment, $runningBalance);

        return $ledger;
    }

    public function debitPoints(LoyaltyEnrollment $enrollment, int $points, ?string $description = null, ?string $triggeredByEvent = null): ?PointsLedger
    {
        $lastLedger = PointsLedger::where('enrollment_id', $enrollment->id)
            ->orderByDesc('transaction_date')
            ->first();

        $currentBalance = $lastLedger?->running_balance ?? 0;

        if ($currentBalance < $points) {
            return null;
        }

        $runningBalance = $currentBalance - $points;

        $ledger = PointsLedger::create([
            'enrollment_id' => $enrollment->id,
            'contact_id' => $enrollment->contact_id,
            'program_id' => $enrollment->program_id,
            'type' => 'debit',
            'points_amount' => $points,
            'running_balance' => $runningBalance,
            'description' => $description,
            'triggered_by_event' => $triggeredByEvent,
            'transaction_date' => now(),
        ]);

        $this->evaluateTierChange($enrollment, $runningBalance);

        return $ledger;
    }

    public function getBalance(LoyaltyEnrollment $enrollment): int
    {
        $lastLedger = PointsLedger::where('enrollment_id', $enrollment->id)
            ->orderByDesc('transaction_date')
            ->first();

        return $lastLedger?->running_balance ?? 0;
    }

    public function getLedger(LoyaltyEnrollment $enrollment, int $perPage = 25, string $sort = 'desc'): Paginator
    {
        $query = PointsLedger::where('enrollment_id', $enrollment->id)
            ->orderBy('transaction_date', $sort === 'asc' ? 'asc' : 'desc');

        return $query->paginate($perPage);
    }

    private function evaluateTierChange(LoyaltyEnrollment $enrollment, int $newBalance): void
    {
        $contact = $enrollment->contact;
        $program = $enrollment->program;

        $tiers = $program->tiers()->orderBy('min_points_threshold')->get();

        $newTier = $tiers->last();
        foreach ($tiers as $tier) {
            if ($newBalance >= $tier->min_points_threshold) {
                $newTier = $tier;
            }
        }

        if ($newTier && $contact->loyalty_tier !== $newTier->name) {
            $oldTier = $contact->loyalty_tier;
            $contact->update(['loyalty_tier' => $newTier->name]);

            // Dispatch tier change notification
            SendTierChangeNotification::dispatch($contact, $oldTier, $newTier->name);
        }
    }
}

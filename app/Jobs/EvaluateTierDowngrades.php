<?php

namespace App\Jobs;

use App\Models\LoyaltyEnrollment;
use App\Models\PointsLedger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateTierDowngrades implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $enrollments = LoyaltyEnrollment::where('is_active', true)->get();

        foreach ($enrollments as $enrollment) {
            $lastLedger = PointsLedger::where('enrollment_id', $enrollment->id)
                ->orderByDesc('transaction_date')
                ->first();

            $currentBalance = $lastLedger?->running_balance ?? 0;

            $contact = $enrollment->contact;
            $program = $enrollment->program;

            if (! $contact || ! $program) {
                continue;
            }

            $currentTier = $program->tiers()
                ->where('name', $contact->loyalty_tier)
                ->first();

            if (! $currentTier) {
                continue;
            }

            if ($currentBalance < $currentTier->min_points_threshold) {
                $newTier = $program->tiers()
                    ->where('min_points_threshold', '<=', $currentBalance)
                    ->orderByDesc('min_points_threshold')
                    ->first();

                if ($newTier && $newTier->id !== $currentTier->id) {
                    $oldTier = $contact->loyalty_tier;
                    $contact->update(['loyalty_tier' => $newTier->name]);

                    SendTierChangeNotification::dispatch($contact, $oldTier, $newTier->name);
                }
            }
        }
    }
}

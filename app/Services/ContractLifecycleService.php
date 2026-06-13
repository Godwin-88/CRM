<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractMilestone;
use App\Models\ContractSignatory;
use App\Models\User;

class ContractLifecycleService
{
    public function advanceStatus(Contract $contract, string $newStatus, ?string $reason = null, ?User $actor = null): void
    {
        $allowed = [
            Contract::STATUS_DRAFT => [Contract::STATUS_SENT],
            Contract::STATUS_SENT => [Contract::STATUS_SIGNED, Contract::STATUS_DECLINED],
            Contract::STATUS_SIGNED => [Contract::STATUS_ACTIVE],
            Contract::STATUS_ACTIVE => [Contract::STATUS_EXPIRING, Contract::STATUS_TERMINATED],
            Contract::STATUS_EXPIRING => [Contract::STATUS_EXPIRED, Contract::STATUS_ACTIVE],
            Contract::STATUS_EXPIRED => [],
            Contract::STATUS_DECLINED => [],
            Contract::STATUS_TERMINATED => [],
        ];

        $current = $contract->status;
        if (! in_array($newStatus, $allowed[$current] ?? [], true)) {
            throw new \InvalidArgumentException("Invalid status transition from {$current} to {$newStatus}.");
        }

        $oldStatus = $current;

        $contract->update([
            'status' => $newStatus,
            'signed_at' => $newStatus === Contract::STATUS_SIGNED ? now() : $contract->signed_at,
            'activated_at' => $newStatus === Contract::STATUS_ACTIVE ? now() : $contract->activated_at,
            'terminated_at' => $newStatus === Contract::STATUS_TERMINATED ? now() : $contract->terminated_at,
            'termination_reason' => $newStatus === Contract::STATUS_TERMINATED ? $reason : $contract->termination_reason,
        ]);

        activity()
            ->performedOn($contract)
            ->causedBy($actor ?? auth()->user())
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
            ])
            ->log('contract_status_changed');
    }

    public function allSigned(Contract $contract): bool
    {
        $pending = $contract->signatories()->whereIn('status', [
            ContractSignatory::STATUS_PENDING,
            ContractSignatory::STATUS_VIEWED,
        ])->exists();

        return ! $pending;
    }

    public function completeMilestone(ContractMilestone $milestone, string $completionNote): void
    {
        if ($milestone->status === ContractMilestone::STATUS_COMPLETED) {
            return;
        }

        $milestone->update([
            'status' => ContractMilestone::STATUS_COMPLETED,
            'completed_at' => now(),
            'completion_note' => $completionNote,
        ]);

        activity()
            ->performedOn($milestone->contract)
            ->withProperties([
                'milestone_id' => $milestone->id,
                'milestone_name' => $milestone->name,
            ])
            ->log('milestone_completed');
    }
}

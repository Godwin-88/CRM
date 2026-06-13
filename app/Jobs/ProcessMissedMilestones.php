<?php

namespace App\Jobs;

use App\Models\ContractMilestone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMissedMilestones implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function handle(): void
    {
        $missed = ContractMilestone::where('status', ContractMilestone::STATUS_PENDING)
            ->where('due_date', '<', now()->startOfDay())
            ->whereNull('deleted_at')
            ->get();

        foreach ($missed as $milestone) {
            $milestone->update(['status' => ContractMilestone::STATUS_MISSED]);

            $contract = $milestone->contract;
            activity()
                ->performedOn($contract)
                ->withProperties([
                    'milestone_id' => $milestone->id,
                    'milestone_name' => $milestone->name,
                ])
                ->log('milestone_missed');
        }
    }
}

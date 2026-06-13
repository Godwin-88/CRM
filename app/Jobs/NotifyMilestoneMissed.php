<?php

namespace App\Jobs;

use App\Models\Contract;
use App\Models\ContractMilestone;
use App\Notifications\MilestoneMissedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyMilestoneMissed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $contractId, public int $milestoneId) {}

    public function handle(): void
    {
        $contract = Contract::findOrFail($this->contractId);
        $milestone = ContractMilestone::findOrFail($this->milestoneId);

        $recipient = $contract->accountManager ?? $contract->createdBy;
        if (! $recipient) {
            return;
        }

        Notification::route('reverb', ['user_id' => $recipient->id])
            ->notify(new MilestoneMissedNotification($milestone, $contract));
    }
}

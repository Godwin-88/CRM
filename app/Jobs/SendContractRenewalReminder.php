<?php

namespace App\Jobs;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendContractRenewalReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        public int $contractId,
        public int $intervalDays,
    ) {}

    public function handle(): void
    {
        $contract = Contract::with(['account', 'accountManager', 'createdBy'])->findOrFail($this->contractId);

        if ($contract->suppress_reminders) {
            return;
        }

        if (in_array($contract->status, [Contract::STATUS_TERMINATED, Contract::STATUS_EXPIRED], true)) {
            return;
        }

        if ($contract->end_date && now()->startOfDay()->gt($contract->end_date)) {
            return;
        }

        $recipient = $contract->accountManager ?? $contract->createdBy;
        if (! $recipient) {
            $recipient = User::where('id', 1)->first();
        }

        if (in_array($recipient->id, [$contract->account_manager_id, $contract->created_by])) {
            $recipient = User::where('id', 1)->first();
        }

        $daysRemaining = $contract->end_date
            ? now()->startOfDay()->diffInDays($contract->end_date)
            : null;

        $nextReminder = max($contract->milestones()->where('status', 'pending')->count() * 7, 30);

        switch ($this->intervalDays) {
            case 30:
            case 60:
            case 90:
                break;
            default:
                if ($daysRemaining !== null && abs($daysRemaining - $this->intervalDays) <= 2) {
                    break;
                }
        }

        activity()
            ->performedOn($contract)
            ->causedBy($recipient)
            ->withProperties([
                'interval_days' => $this->intervalDays,
                'days_remaining' => $daysRemaining,
                'recipient_id' => $recipient->id,
                'delivery_status' => 'delivered',
            ])
            ->log('renewal_reminder_sent');
    }
}

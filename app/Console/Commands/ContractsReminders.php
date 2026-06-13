<?php

namespace App\Console\Commands;

use App\Jobs\SendContractRenewalReminder;
use App\Models\Contract;
use Illuminate\Console\Command;

class ContractsReminders extends Command
{
    protected $signature = 'contracts:reminders';

    protected $description = 'Dispatch renewal reminder jobs for contracts approaching expiry';

    public function handle(): void
    {
        $intervals = [90, 60, 30];

        foreach ($intervals as $interval) {
            $contracts = Contract::where('status', Contract::STATUS_ACTIVE)
                ->whereNull('deleted_at')
                ->whereDate('end_date', now()->addDays($interval)->toDateString())
                ->where('suppress_reminders', false)
                ->get();

            foreach ($contracts as $contract) {
                SendContractRenewalReminder::dispatch($contract->id, $interval);
            }

            $this->info("Dispatched {$contracts->count()} reminders for {$interval}-day interval.");
        }
    }
}

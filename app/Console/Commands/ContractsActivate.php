<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Illuminate\Console\Command;

class ContractsActivate extends Command
{
    protected $signature = 'contracts:activate';

    protected $description = 'Advance Signed contracts to Active when start date is reached';

    public function handle(): void
    {
        $count = Contract::where('status', Contract::STATUS_SIGNED)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereNull('deleted_at')
            ->count();

        if ($count === 0) {
            $this->info('No contracts to activate.');

            return;
        }

        Contract::where('status', Contract::STATUS_SIGNED)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereNull('deleted_at')
            ->each(function ($contract) {
                $contract->update([
                    'status' => Contract::STATUS_ACTIVE,
                    'activated_at' => now(),
                ]);
            });

        $this->info("Activated {$count} contracts.");
    }
}

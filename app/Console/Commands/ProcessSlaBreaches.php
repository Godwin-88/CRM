<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Services\SlaService;
use Illuminate\Console\Command;

class ProcessSlaBreaches extends Command
{
    protected $signature = 'sla:check-breaches';

    protected $description = 'Check for SLA breaches and send notifications';

    public function handle(SlaService $slaService): int
    {
        $slaService->checkBreaches();

        $this->info('SLA breaches checked successfully.');

        return self::SUCCESS;
    }

    public function autoCloseResolved(): void
    {
        Ticket::where('status', 'resolved')
            ->where('resolved_at', '<', now()->subDays(7))
            ->update(['status' => 'closed', 'closed_at' => now()]);
    }
}

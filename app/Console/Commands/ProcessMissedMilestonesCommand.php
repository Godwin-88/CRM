<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMissedMilestones;
use Illuminate\Console\Command;

class ProcessMissedMilestonesCommand extends Command
{
    protected $signature = 'contracts:milestones';

    protected $description = 'Mark overdue milestones as missed';

    public function handle(): void
    {
        ProcessMissedMilestones::dispatch();
        $this->info('Missed milestones job dispatched.');
    }
}

<?php

namespace App\Console\Commands;

use App\Services\ScoringService;
use Illuminate\Console\Command;

class RecalculateScores extends Command
{
    protected $signature = 'scores:recalculate';

    protected $description = 'Recalculate scores for all contacts';

    public function handle(ScoringService $scoringService): void
    {
        $this->info('Recalculating contact scores...');
        $scoringService->recalculateAll();
        $this->info('All contact scores recalculated.');
    }
}

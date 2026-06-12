<?php

namespace App\Console\Commands;

use App\Services\SegmentService;
use Illuminate\Console\Command;

class RefreshSegmentCounts extends Command
{
    protected $signature = 'segments:refresh-counts';
    protected $description = 'Refresh cached contact counts for all segments';

    public function handle(SegmentService $segmentService): void
    {
        $this->info('Refreshing segment contact counts...');
        $segmentService->refreshAllCounts();
        $this->info('All segment counts refreshed.');
    }
}
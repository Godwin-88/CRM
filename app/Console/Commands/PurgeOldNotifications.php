<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeOldNotifications extends Command
{
    protected $signature = 'notifications:purge';

    protected $description = 'Purge notifications older than 30 days';

    public function handle(): int
    {
        $deleted = DB::table('notifications')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        $this->info("Deleted {$deleted} old notifications.");

        return self::SUCCESS;
    }
}
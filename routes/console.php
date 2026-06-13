<?php

use App\Models\Ticket;
use App\Services\SlaService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Scheduled Tasks for 4.1 Features ────────────────────────

// Refresh segment cached counts hourly
Schedule::command('segments:refresh-counts')->hourly();

// Recalculate contact scores daily
Schedule::command('scores:recalculate')->daily();

// Process SLA breaches every 15 minutes
Schedule::call(function () {
    app(SlaService::class)->checkBreaches();
})->everyFifteenMinutes();

// Auto-close resolved tickets after 7 days
Schedule::call(function () {
    Ticket::where('status', 'resolved')
        ->where('resolved_at', '<', now()->subDays(7))
        ->update(['status' => 'closed', 'closed_at' => now()]);
})->daily();

// Contracts lifecycle
Schedule::command('contracts:activate')->dailyAt('01:00');
Schedule::command('contracts:reminders')->dailyAt('02:00');
Schedule::command('contracts:milestones')->dailyAt('03:00');
Schedule::call(ProcessMissedMilestones::class)->dailyAt('03:30');

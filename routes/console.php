<?php

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
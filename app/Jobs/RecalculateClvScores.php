<?php

namespace App\Jobs;

use App\Models\ClvCalculation;
use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ClvCalculationService;

class RecalculateClvScores implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ClvCalculationService $service): void
    {
        $service->recalculateAll();
    }
}

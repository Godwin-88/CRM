<?php

namespace App\Jobs;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateClvScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Contact $contact) {}

    public function handle(): void
    {
        // Business logic for CLV calculation
        // e.g. sum of closed deals, etc.
        $clv = $this->contact->deals()->where('stage', 'closed_won')->sum('value');
        $this->contact->update(['clv_score' => $clv]);
    }
}

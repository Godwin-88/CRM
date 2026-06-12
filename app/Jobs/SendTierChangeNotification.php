<?php

namespace App\Jobs;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTierChangeNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Contact $contact,
        public string $oldTier,
        public string $newTier
    ) {}

    public function handle(): void
    {
        $contact->notify(new \App\Notifications\TierChangedNotification(
            $this->oldTier,
            $this->newTier
        ));
    }
}

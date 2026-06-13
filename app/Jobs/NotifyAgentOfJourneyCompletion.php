<?php

namespace App\Jobs;

use App\Models\JourneyCompletion;
use App\Notifications\JourneyCompletedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyAgentOfJourneyCompletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public JourneyCompletion $completion,
        public int $agentId
    ) {}

    public function handle(): void
    {
        $contact = $this->completion->contact;
        $journey = $this->completion->journey;

        if (! $contact || ! $journey) {
            return;
        }

        $contact->owner?->notify(new JourneyCompletedNotification(
            $contact,
            $journey
        ));
    }
}

<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Notifications\CsatRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendCsatRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public int $delaySeconds = 3600
    ) {}

    public function handle(): void
    {
        if ($this->ticket->rating) {
            return;
        }

        if ($this->ticket->is_agent_created) {
            return;
        }

        $contact = $this->ticket->contact;

        if ($contact->preferred_channel === 'email') {
            Notification::route('mail', $contact->email)
                ->notify(new CsatRequest($this->ticket));
        }
    }
}
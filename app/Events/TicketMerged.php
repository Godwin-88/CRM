<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketMerged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Ticket $sourceTicket,
        public Ticket $targetTicket
    ) {}
}

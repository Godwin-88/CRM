<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketEscalated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public User $escalatedBy,
        public ?User $manager = null
    ) {}
}

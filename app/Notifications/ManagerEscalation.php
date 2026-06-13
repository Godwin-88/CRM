<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ManagerEscalation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public $escalatedBy
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'ticket_escalated',
            'ticket_id' => $this->ticket->id,
            'ticket_subject' => $this->ticket->subject,
            'escalated_by_id' => $this->escalatedBy->id,
            'escalation_reason' => $this->ticket->escalation_reason,
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public ?User $assigner = null
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ticket Assigned: '.$this->ticket->subject)
            ->line('A ticket has been assigned to you:')
            ->line($this->ticket->subject)
            ->action('View Ticket', url('/tickets/'.$this->ticket->id))
            ->line('Please respond to this ticket within the SLA timeframe.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'ticket_assigned',
            'ticket_id' => $this->ticket->id,
            'ticket_subject' => $this->ticket->subject,
            'assigner_id' => $this->assigner?->id,
        ];
    }
}

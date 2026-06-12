<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlaBreachWarning extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $breachType
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('SLA Warning: ' . $this->ticket->subject)
            ->line('The following ticket is approaching its SLA deadline:')
            ->line($this->ticket->subject)
            ->line('Breach type: ' . ucfirst(str_replace('_', ' ', $this->breachType)))
            ->action('View Ticket', url('/tickets/' . $this->ticket->id))
            ->line('Please take action immediately.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'sla_breach_warning',
            'ticket_id' => $this->ticket->id,
            'ticket_subject' => $this->ticket->subject,
            'breach_type' => $this->breachType,
        ];
    }
}
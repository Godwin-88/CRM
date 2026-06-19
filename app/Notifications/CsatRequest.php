<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CsatRequest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $baseUrl = config('app.url');
        $ticketRef = $this->ticket->id;

        return (new MailMessage)
            ->subject('[Ticket #'.$ticketRef.'] How was your support experience?')
            ->greeting('Hello '.$this->ticket->contact->first_name.',')
            ->line('Your ticket #'.$ticketRef.' has been resolved. Please rate your support experience:')
            ->line('Click a score below to submit your rating (no login required):')
            ->action('Rate 5 (Excellent)', $baseUrl.'/tickets/'.$ticketRef.'/rate/5')
            ->line('Or click a different score:')
            ->line('1 (Poor) | 2 (Fair) | 3 (Good) | 4 (Very Good) | 5 (Excellent)')
            ->line('Thank you for your feedback!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'csat_request',
            'ticket_id' => $this->ticket->id,
            'ticket_subject' => $this->ticket->subject,
        ];
    }
}

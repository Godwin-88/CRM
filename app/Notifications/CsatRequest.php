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

        return (new MailMessage())
            ->subject('How was your support experience? Ticket #' . $this->ticket->id)
            ->line('Your ticket has been resolved. Please rate your support experience:')
            ->line('Click a score below to submit your rating (no login required):')
            ->line('1 (Poor) | 2 (Fair) | 3 (Good) | 4 (Very Good) | 5 (Excellent)')
            ->action('Rate Your Experience', $baseUrl . '/tickets/' . $this->ticket->id . '/rate')
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
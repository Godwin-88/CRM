<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketInternalNote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InternalNoteMentioned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public TicketInternalNote $note,
        public $mentionedUser = null
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'internal_note_mentioned',
            'ticket_id' => $this->ticket->id,
            'ticket_subject' => $this->ticket->subject,
            'note_id' => $this->note->id,
            'url' => '/tickets/' . $this->ticket->id . '#note-' . $this->note->id,
        ];
    }
}
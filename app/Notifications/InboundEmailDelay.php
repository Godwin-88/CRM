<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InboundEmailDelay extends Notification
{
    use Queueable;

    public function __construct(
        public int $delayMinutes,
        public array $emailData
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Inbound Email Processing Delay Alert')
            ->line("An inbound email has been delayed in processing for {$this->delayMinutes} minutes.")
            ->line("Email subject: {$this->emailData['subject']}")
            ->line("From: {$this->emailData['from']}")
            ->action('View Support Tickets', url('/support/tickets'));
    }
}
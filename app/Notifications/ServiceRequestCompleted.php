<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceRequestCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ServiceRequest $serviceRequest
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Service request completed')
            ->line('Service request '.$this->serviceRequest->id.' has been completed.')
            ->action('View Service Request', url('/service-requests/'.$this->serviceRequest->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'service_request_completed',
            'service_request_id' => $this->serviceRequest->id,
            'contact_id' => $this->serviceRequest->contact_id,
            'account_id' => $this->serviceRequest->account_id,
        ];
    }
}

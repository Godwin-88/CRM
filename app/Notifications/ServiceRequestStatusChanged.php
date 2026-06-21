<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceRequestStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ServiceRequest $serviceRequest,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Service request status changed: '.$this->serviceRequest->id)
            ->line('Service request status changed from '.$this->oldStatus.' to '.$this->newStatus.'.')
            ->action('View Service Request', url('/service-requests/'.$this->serviceRequest->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'service_request_status_changed',
            'service_request_id' => $this->serviceRequest->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'contact_id' => $this->serviceRequest->contact_id,
            'account_id' => $this->serviceRequest->account_id,
        ];
    }
}

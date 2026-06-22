<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceRequestDocumentRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ServiceRequest $serviceRequest,
        public $documentRequest
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Document requested for service request')
            ->line($this->documentRequest->title.' is required for service request '.$this->serviceRequest->id.'.')
            ->action('View Service Request', url('/service-requests/'.$this->serviceRequest->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'service_request_document_requested',
            'service_request_id' => $this->serviceRequest->id,
            'document_request_id' => $this->documentRequest->id,
            'title' => $this->documentRequest->title,
            'contact_id' => $this->serviceRequest->contact_id,
            'account_id' => $this->serviceRequest->account_id,
        ];
    }
}

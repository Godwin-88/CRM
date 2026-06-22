<?php

namespace App\Notifications;

use App\Models\CaseRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CaseRecord $caseRecord,
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
            ->subject('Case status changed: '.$this->caseRecord->case_number)
            ->line('Case status changed from '.$this->oldStatus.' to '.$this->newStatus.'.')
            ->action('View Case', url('/cases/'.$this->caseRecord->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'case_status_changed',
            'case_id' => $this->caseRecord->id,
            'case_number' => $this->caseRecord->case_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'primary_contact_id' => $this->caseRecord->primary_contact_id,
            'primary_account_id' => $this->caseRecord->primary_account_id,
        ];
    }
}

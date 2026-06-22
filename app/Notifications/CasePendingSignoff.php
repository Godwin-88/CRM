<?php

namespace App\Notifications;

use App\Models\CaseRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CasePendingSignoff extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CaseRecord $caseRecord
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Case sign-off required')
            ->line('Case '.$this->caseRecord->case_number.' requires sign-off before closure.')
            ->action('Review Case', url('/cases/'.$this->caseRecord->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'case_signoff_required',
            'case_id' => $this->caseRecord->id,
            'case_number' => $this->caseRecord->case_number,
            'primary_contact_id' => $this->caseRecord->primary_contact_id,
            'primary_account_id' => $this->caseRecord->primary_account_id,
        ];
    }
}

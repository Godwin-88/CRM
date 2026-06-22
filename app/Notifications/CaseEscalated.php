<?php

namespace App\Notifications;

use App\Models\CaseRecord;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CaseEscalated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CaseRecord $caseRecord,
        public User $escalatedBy,
        public string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'case_escalated',
            'case_id' => $this->caseRecord->id,
            'case_number' => $this->caseRecord->case_number,
            'escalated_by_id' => $this->escalatedBy->id,
            'escalation_reason' => $this->reason,
            'priority' => $this->caseRecord->priority,
            'primary_contact_id' => $this->caseRecord->primary_contact_id,
            'primary_account_id' => $this->caseRecord->primary_account_id,
        ];
    }
}

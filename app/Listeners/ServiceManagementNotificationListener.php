<?php

namespace App\Listeners;

use App\Events\CaseClosed;
use App\Events\CaseCreated;
use App\Events\CaseEscalated;
use App\Events\CaseSignoffRequired;
use App\Events\CaseStatusChanged;
use App\Events\ServiceRequestClosed;
use App\Events\ServiceRequestCompleted;
use App\Events\ServiceRequestCreated;
use App\Events\ServiceRequestDocumentRequested;
use App\Events\ServiceRequestStatusChanged;
use App\Events\WebhookEventOccurred;
use App\Models\User;
use App\Notifications\CaseClosed as CaseClosedNotification;
use App\Notifications\CaseEscalated as CaseEscalatedNotification;
use App\Notifications\CasePendingSignoff;
use App\Notifications\CaseStatusChanged as CaseStatusChangedNotification;
use App\Notifications\ServiceRequestClosed as ServiceRequestClosedNotification;
use App\Notifications\ServiceRequestCompleted as ServiceRequestCompletedNotification;
use App\Notifications\ServiceRequestDocumentRequested as ServiceRequestDocumentRequestedNotification;
use App\Notifications\ServiceRequestStatusChanged as ServiceRequestStatusChangedNotification;
class ServiceManagementNotificationListener
{
    public function __invoke(object $event): void
    {
        match (true) {
            $event instanceof ServiceRequestCreated => $this->handleServiceRequestCreated($event),
            $event instanceof ServiceRequestStatusChanged => $this->handleServiceRequestStatusChanged($event),
            $event instanceof ServiceRequestCompleted => $this->handleServiceRequestCompleted($event),
            $event instanceof ServiceRequestClosed => $this->handleServiceRequestClosed($event),
            $event instanceof ServiceRequestDocumentRequested => $this->handleServiceRequestDocumentRequested($event),
            $event instanceof CaseCreated => $this->handleCaseCreated($event),
            $event instanceof CaseStatusChanged => $this->handleCaseStatusChanged($event),
            $event instanceof CaseEscalated => $this->handleCaseEscalated($event),
            $event instanceof CaseSignoffRequired => $this->handleCaseSignoffRequired($event),
            $event instanceof CaseClosed => $this->handleCaseClosed($event),
            default => null,
        };
    }

    public function handleServiceRequestCreated(ServiceRequestCreated $event): void
    {
        $this->dispatchWebhook('service_request.created', $this->serviceRequestPayload($event->serviceRequest));
        $this->notifyAssignee($event->serviceRequest, new ServiceRequestStatusChangedNotification($event->serviceRequest, 'created', $event->serviceRequest->status));
    }

    public function handleServiceRequestStatusChanged(ServiceRequestStatusChanged $event): void
    {
        $this->dispatchWebhook('service_request.status_changed', $this->serviceRequestPayload($event->serviceRequest, $event->oldStatus, $event->newStatus));

        $this->notifyAssignee($event->serviceRequest, new ServiceRequestStatusChangedNotification($event->serviceRequest, $event->oldStatus, $event->newStatus));

        if ($event->serviceRequest->requester) {
            $event->serviceRequest->requester->notify(new ServiceRequestStatusChangedNotification($event->serviceRequest, $event->oldStatus, $event->newStatus));
        }
    }

    public function handleServiceRequestCompleted(ServiceRequestCompleted $event): void
    {
        $this->dispatchWebhook('service_request.completed', $this->serviceRequestPayload($event->serviceRequest));
        $this->notifyRequester($event->serviceRequest, new ServiceRequestCompletedNotification($event->serviceRequest));
    }

    public function handleServiceRequestClosed(ServiceRequestClosed $event): void
    {
        $this->dispatchWebhook('service_request.closed', $this->serviceRequestPayload($event->serviceRequest));
        $this->notifyRequester($event->serviceRequest, new ServiceRequestClosedNotification($event->serviceRequest));
    }

    public function handleServiceRequestDocumentRequested(ServiceRequestDocumentRequested $event): void
    {
        $this->dispatchWebhook('service_request.document_requested', $this->serviceRequestPayload($event->serviceRequest));
        $this->notifyRequester($event->serviceRequest, new ServiceRequestDocumentRequestedNotification($event->serviceRequest, $event->documentRequest));
    }

    public function handleCaseCreated(CaseCreated $event): void
    {
        $this->dispatchWebhook('case.created', $this->casePayload($event->caseRecord));
        $this->notifyOwner($event->caseRecord, new CaseStatusChangedNotification($event->caseRecord, 'created', $event->caseRecord->status));
    }

    public function handleCaseStatusChanged(CaseStatusChanged $event): void
    {
        $this->dispatchWebhook('case.status_changed', $this->casePayload($event->caseRecord, $event->oldStatus, $event->newStatus));
        $this->notifyOwner($event->caseRecord, new CaseStatusChangedNotification($event->caseRecord, $event->oldStatus, $event->newStatus));
    }

    public function handleCaseEscalated(CaseEscalated $event): void
    {
        $this->dispatchWebhook('case.escalated', $this->casePayload($event->caseRecord));
        $this->notifyOwner($event->caseRecord, new CaseEscalatedNotification($event->caseRecord, $event->escalatedBy, $event->reason));
        $this->notifyManagers(new CaseEscalatedNotification($event->caseRecord, $event->escalatedBy, $event->reason));
    }

    public function handleCaseSignoffRequired(CaseSignoffRequired $event): void
    {
        $this->dispatchWebhook('case.signoff_required', $this->casePayload($event->caseRecord));
        $this->notifyManagers(new CasePendingSignoff($event->caseRecord));
    }

    public function handleCaseClosed(CaseClosed $event): void
    {
        $this->dispatchWebhook('case.closed', $this->casePayload($event->caseRecord));
        $this->notifyOwner($event->caseRecord, new CaseClosedNotification($event->caseRecord));
    }

    protected function serviceRequestPayload($serviceRequest, ?string $oldStatus = null, ?string $newStatus = null): array
    {
        return [
            'id' => $serviceRequest->id,
            'catalog_item_id' => $serviceRequest->catalog_item_id,
            'contact_id' => $serviceRequest->contact_id,
            'account_id' => $serviceRequest->account_id,
            'channel' => $serviceRequest->channel,
            'source_identifier' => $serviceRequest->source_identifier,
            'old_status' => $oldStatus,
            'new_status' => $newStatus ?? $serviceRequest->status,
            'current_status' => $serviceRequest->status,
            'priority' => $serviceRequest->priority,
            'assigned_to' => $serviceRequest->assigned_to,
            'team_id' => $serviceRequest->team_id,
            'case_record_id' => $serviceRequest->case_record_id,
            'sla_instance_id' => $serviceRequest->sla_instance_id,
            'created_at' => $serviceRequest->created_at?->toIso8601String(),
            'updated_at' => $serviceRequest->updated_at?->toIso8601String(),
        ];
    }

    protected function casePayload($caseRecord, ?string $oldStatus = null, ?string $newStatus = null): array
    {
        return [
            'id' => $caseRecord->id,
            'case_number' => $caseRecord->case_number,
            'title' => $caseRecord->title,
            'type' => $caseRecord->type,
            'old_status' => $oldStatus,
            'new_status' => $newStatus ?? $caseRecord->status,
            'current_status' => $caseRecord->status,
            'priority' => $caseRecord->priority,
            'owner_id' => $caseRecord->owner_id,
            'primary_contact_id' => $caseRecord->primary_contact_id,
            'primary_account_id' => $caseRecord->primary_account_id,
            'sla_instance_id' => $caseRecord->sla_instance_id,
            'signoff_status' => $caseRecord->signoff_status,
            'created_at' => $caseRecord->created_at?->toIso8601String(),
            'updated_at' => $caseRecord->updated_at?->toIso8601String(),
        ];
    }

    protected function dispatchWebhook(string $event, array $payload): void
    {
        WebhookEventOccurred::dispatch($event, $payload);
    }

    protected function notifyAssignee($serviceRequest, $notification): void
    {
        if ($serviceRequest->assignee) {
            $serviceRequest->assignee->notify($notification);
        }
    }

    protected function notifyRequester($serviceRequest, $notification): void
    {
        if ($serviceRequest->requester) {
            $serviceRequest->requester->notify($notification);
        }
    }

    protected function notifyOwner($caseRecord, $notification): void
    {
        if ($caseRecord->owner) {
            $caseRecord->owner->notify($notification);
        }
    }

    protected function notifyManagers($notification): void
    {
        User::role('manager')->get()->each(fn (User $manager) => $manager->notify($notification));
    }
}

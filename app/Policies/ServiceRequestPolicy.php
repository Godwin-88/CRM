<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('service_requests.view');
    }

    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        if ($user->can('service_requests.view')) {
            return true;
        }

        return $serviceRequest->contact_id && $serviceRequest->contact?->owner_id === $user->id
            || $serviceRequest->requester_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('service_requests.create');
    }

    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        if ($user->can('service_requests.update')) {
            return true;
        }

        return $serviceRequest->assigned_to === $user->id;
    }

    public function close(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->can('service_requests.close')
            || ($serviceRequest->assigned_to === $user->id && $user->can('service_requests.update'));
    }

    public function reopen(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->can('service_requests.reopen')
            || ($serviceRequest->assigned_to === $user->id && $user->can('service_requests.update'));
    }

    public function export(User $user): bool
    {
        return $user->can('service_requests.export');
    }
}

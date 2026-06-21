<?php

namespace App\Policies;

use App\Models\CaseRecord;
use App\Models\User;

class CaseRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cases.view');
    }

    public function view(User $user, CaseRecord $caseRecord): bool
    {
        if ($user->can('cases.view')) {
            return true;
        }

        return $caseRecord->owner_id === $user->id
            || ($caseRecord->primary_contact_id && $caseRecord->primaryContact?->owner_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can('cases.create');
    }

    public function update(User $user, CaseRecord $caseRecord): bool
    {
        if ($user->can('cases.update')) {
            return true;
        }

        return $caseRecord->owner_id === $user->id;
    }

    public function close(User $user, CaseRecord $caseRecord): bool
    {
        return $user->can('cases.close')
            || ($caseRecord->owner_id === $user->id && $user->can('cases.update'));
    }

    public function reopen(User $user, CaseRecord $caseRecord): bool
    {
        return $user->can('cases.reopen')
            || ($caseRecord->owner_id === $user->id && $user->can('cases.update'));
    }

    public function signoff(User $user, CaseRecord $caseRecord): bool
    {
        return $user->can('cases.signoff')
            || ($caseRecord->owner_id === $user->id && $user->can('cases.update'));
    }

    public function export(User $user): bool
    {
        return $user->can('cases.export');
    }
}

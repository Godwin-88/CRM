<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'manager', 'agent']);
    }

    public function view(User $user, Contract $contract): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            return true;
        }

        return $user->id === $contract->account?->account_manager_id
            || $user->id === $contract->contact?->owner_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'manager', 'agent']);
    }

    public function update(User $user, Contract $contract): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->hasRole(['admin']);
    }

    public function sign(User $user, Contract $contract): bool
    {
        return $user->hasRole(['admin', 'manager', 'agent']);
    }

    public function manageTemplates(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function viewLegal(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function manageLegal(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }
}

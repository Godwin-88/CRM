<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

class DealPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'manager', 'agent']);
    }

    public function view(User $user, Deal $deal): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            return true;
        }

        return $user->id === $deal->owner_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'manager', 'agent']);
    }

    public function update(User $user, Deal $deal): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            return true;
        }

        return $user->id === $deal->owner_id;
    }

    public function delete(User $user, Deal $deal): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function managePipeline(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function manageAutomations(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }
}

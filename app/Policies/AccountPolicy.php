<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view accounts');
    }

    public function view(User $user, Account $account): bool
    {
        return $user->can('view accounts') || $user->id === $account->account_manager_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create accounts');
    }

    public function update(User $user, Account $account): bool
    {
        return $user->can('edit accounts') || $user->id === $account->account_manager_id;
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->can('delete accounts');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Account $account): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Account $account): bool
    {
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view contacts');
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->can('view contacts') || $user->id === $contact->owner_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create contacts');
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->can('edit contacts') || $user->id === $contact->owner_id;
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->can('delete contacts');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Contact $contact): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Contact $contact): bool
    {
        return false;
    }
}

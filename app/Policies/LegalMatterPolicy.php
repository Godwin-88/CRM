<?php

namespace App\Policies;

use App\Models\LegalMatter;
use App\Models\User;

class LegalMatterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function view(User $user, LegalMatter $legalMatter): bool
    {
        return $user->hasRole(['admin', 'manager'])
            || $legalMatter->contact_id && $user->id === $legalMatter->contact?->owner_id
            || $legalMatter->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function update(User $user, LegalMatter $legalMatter): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('manager')
            && ($legalMatter->assigned_to === $user->id
                || is_null($legalMatter->assigned_to));
    }

    public function delete(User $user, LegalMatter $legalMatter): bool
    {
        return $user->hasRole(['admin']);
    }

    public function manage(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }
}

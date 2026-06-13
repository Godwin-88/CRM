<?php

namespace App\Policies;

use App\Models\BankingRelationship;
use App\Models\User;

class BankingRelationshipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('banking.view');
    }

    public function view(User $user, BankingRelationship $relationship): bool
    {
        return $user->hasPermissionTo('banking.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('banking.manage');
    }

    public function update(User $user, BankingRelationship $relationship): bool
    {
        return $user->hasPermissionTo('banking.manage');
    }

    public function delete(User $user, BankingRelationship $relationship): bool
    {
        return $user->hasPermissionTo('banking.manage');
    }

    public function viewFinancials(User $user, BankingRelationship $relationship): bool
    {
        return $user->hasPermissionTo('banking.financials');
    }
}

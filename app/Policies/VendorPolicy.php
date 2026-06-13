<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'manager', 'agent']);
    }

    public function view(User $user, Vendor $vendor): bool
    {
        if ($user->hasRole(['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('vendors.manage');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->hasPermissionTo('vendors.manage');
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->hasPermissionTo('vendors.manage');
    }

    public function viewFinancials(User $user, Vendor $vendor): bool
    {
        return $user->hasPermissionTo('vendors.financials');
    }
}

<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'manager', 'agent']);
    }

    public function view(User $user, PurchaseOrder $po): bool
    {
        if ($user->hasRole(['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('procurement.create');
    }

    public function update(User $user, PurchaseOrder $po): bool
    {
        return $user->hasPermissionTo('procurement.create');
    }

    public function delete(User $user, PurchaseOrder $po): bool
    {
        return $user->hasRole(['admin']);
    }

    public function approve(User $user, PurchaseOrder $po): bool
    {
        return $user->hasPermissionTo('procurement.approve');
    }

    public function recordReceipt(User $user, PurchaseOrder $po): bool
    {
        if ($user->hasRole(['admin', 'manager'])) {
            return true;
        }

        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'manager', 'agent', 'finance-manager']) || $user->hasPermissionTo('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->hasRole(['admin', 'manager'])) {
            return true;
        }

        return $user->id === $invoice->account?->account_manager_id
            || $user->id === $invoice->contact?->owner_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('invoices.manage');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        if ($user->hasRole(['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasRole(['admin']);
    }

    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.payments');
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.manage');
    }
}

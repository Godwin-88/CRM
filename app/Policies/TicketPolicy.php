<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'agent']);
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        return $ticket->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'agent']);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        return $ticket->assigned_to === $user->id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'agent']);
    }

    public function escalate(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'agent']);
    }

    public function resolve(User $user, Ticket $ticket): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        return $ticket->assigned_to === $user->id;
    }

    public function merge(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function split(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Auth\Access\HandlesAuthorization;

class WebhookPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'employee']);
    }

    public function view(User $user, Webhook $webhook): bool
    {
        return $user->hasAnyRole(['admin', 'manager']) || $webhook->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Webhook $webhook): bool
    {
        return $user->hasAnyRole(['admin', 'manager']) || $webhook->created_by === $user->id;
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}

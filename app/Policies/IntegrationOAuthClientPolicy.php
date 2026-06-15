<?php

namespace App\Policies;

use App\Models\IntegrationOAuthClient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IntegrationOAuthClientPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function view(User $user, IntegrationOAuthClient $client): bool
    {
        return $user->hasAnyRole(['admin', 'manager']) || $client->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, IntegrationOAuthClient $client): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function delete(User $user, IntegrationOAuthClient $client): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}

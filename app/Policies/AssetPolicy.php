<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('assets.view');
    }

    public function view(User $user, Asset $asset): bool
    {
        if ($user->hasRole(['admin', 'manager'])) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('assets.manage');
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->hasPermissionTo('assets.manage');
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->hasPermissionTo('assets.manage');
    }

    public function assign(User $user, Asset $asset): bool
    {
        return $user->hasPermissionTo('assets.manage');
    }
}

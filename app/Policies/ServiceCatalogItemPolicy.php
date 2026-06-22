<?php

namespace App\Policies;

use App\Models\ServiceCatalogItem;
use App\Models\User;

class ServiceCatalogItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('services.view');
    }

    public function view(User $user, ServiceCatalogItem $serviceCatalogItem): bool
    {
        return $user->can('services.view')
            || ($serviceCatalogItem->is_active && $serviceCatalogItem->portal_visible);
    }

    public function create(User $user): bool
    {
        return $user->can('services.create') || $user->can('services.manage');
    }

    public function update(User $user, ServiceCatalogItem $serviceCatalogItem): bool
    {
        return $user->can('services.update') || $user->can('services.manage');
    }

    public function delete(User $user, ServiceCatalogItem $serviceCatalogItem): bool
    {
        return $user->can('services.delete') || $user->can('services.manage');
    }

    public function manage(User $user): bool
    {
        return $user->can('services.manage');
    }
}

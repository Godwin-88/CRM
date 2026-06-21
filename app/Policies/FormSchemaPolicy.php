<?php

namespace App\Policies;

use App\Models\FormSchema;
use App\Models\User;

class FormSchemaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('services.view') || $user->can('tickets.view');
    }

    public function view(User $user, FormSchema $formSchema): bool
    {
        return $user->can('services.view') || $user->can('tickets.view');
    }

    public function create(User $user): bool
    {
        return $user->can('services.manage') || $user->can('tickets.manage');
    }

    public function update(User $user, FormSchema $formSchema): bool
    {
        return $user->can('services.manage') || $user->can('tickets.manage');
    }

    public function delete(User $user, FormSchema $formSchema): bool
    {
        return $user->can('services.manage') || $user->can('tickets.manage');
    }
}

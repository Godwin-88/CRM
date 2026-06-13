<?php

namespace App\Policies;

use App\Models\Segment;
use App\Models\User;

class SegmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view segments');
    }

    public function view(User $user, Segment $segment): bool
    {
        return $user->can('view segments');
    }

    public function create(User $user): bool
    {
        return $user->can('manage segments');
    }

    public function update(User $user, Segment $segment): bool
    {
        return $user->can('manage segments');
    }

    public function delete(User $user, Segment $segment): bool
    {
        return $user->can('manage segments');
    }
}

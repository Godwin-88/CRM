<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DiscussionBoard;
use App\Models\DiscussionThread;
use App\Models\DiscussionReply;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiscussionPolicy
{
    use HandlesAuthorization;

    public function view(User $user, DiscussionBoard $board): bool
    {
        $model = $board->boardable;

        return $user->can('view', $model);
    }

    public function create(User $user, $model): bool
    {
        return $user->can('update', $model);
    }

    public function pin(User $user, DiscussionThread $thread): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function resolve(User $user, DiscussionThread $thread): bool
    {
        return $user->hasAnyRole(['admin', 'manager']) ||
            $thread->user_id === $user->id;
    }

    public function addReply(User $user, DiscussionThread $thread): bool
    {
        return $user->can('view', $thread->board->boardable);
    }
}
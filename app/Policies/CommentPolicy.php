<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'employee']);
    }

    public function view(User $user, Comment $comment): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'employee']);
    }

    public function create(User $user, $model): bool
    {
        return $user->can('view', $model);
    }

    public function update(User $user, Comment $comment): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        return $comment->user_id === $user->id && $comment->canEdit($user);
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }

        if ($comment->isDeleted()) {
            return false;
        }

        return $comment->user_id === $user->id;
    }
}
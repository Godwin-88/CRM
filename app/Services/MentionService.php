<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\User;

class MentionService
{
    public function extractMentionIds(string $body): array
    {
        preg_match_all('/@\[([^\]]+)\]\(([^)]+)\)/', $body, $matches);

        return array_map('intval', $matches[2] ?? []);
    }

    public function filterValidUsers(array $userIds, $model): array
    {
        return User::whereIn('id', $userIds)->get()
            ->filter(fn ($user) => $user->can('view', $model))
            ->pluck('id')
            ->toArray();
    }

    public function createMentions(Comment $comment, array $userIds): void
    {
        foreach ($userIds as $userId) {
            CommentMention::updateOrCreate([
                'comment_id' => $comment->id,
                'user_id' => $userId,
            ], [
                'read_at' => null,
            ]);
        }
    }
}
<?php

namespace App\Jobs;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMentionNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Comment $comment,
        protected $model,
        protected array $mentionedUserIds
    ) {}

    public function handle(): void
    {
        foreach ($this->mentionedUserIds as $userId) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $user->notify(new \App\Notifications\CommentMentioned($this->comment, $this->model));
        }
    }
}
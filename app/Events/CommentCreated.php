<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Comment $comment,
        public $model
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('record.'.$this->model->id),
        ];

        if ($this->model instanceof \App\Models\Account || $this->model instanceof \App\Models\Deal) {
            $teamId = $this->resolveTeamId();
            if ($teamId) {
                $channels[] = new PrivateChannel('team.'.$teamId);
            }
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->comment->id,
            'body' => $this->comment->body,
            'user' => $this->comment->user->only(['id', 'name']),
            'created_at' => $this->comment->created_at,
            'commentable_type' => $this->model->getMorphClass(),
            'commentable_id' => $this->model->id,
        ];
    }

    protected function resolveTeamId(): ?string
    {
        if ($this->model instanceof \App\Models\Deal) {
            return $this->model->owner?->teamMembers()->where('is_primary', true)->first()?->team_id;
        }

        if ($this->model instanceof \App\Models\Account) {
            return $this->model->account_manager_id;
        }

        return null;
    }
}
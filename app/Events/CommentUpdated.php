<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class CommentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Comment $comment,
        public $model
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('record.'.$this->model->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->comment->id,
            'body' => $this->comment->body,
            'edited_at' => $this->comment->edited_at,
        ];
    }
}
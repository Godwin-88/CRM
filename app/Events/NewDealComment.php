<?php

namespace App\Events;

use App\Models\DealComment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewDealComment implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DealComment $comment,
        public string $mentionedUserId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->mentionedUserId}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'comment_id' => $this->comment->id,
            'deal_id' => $this->comment->deal_id,
            'body' => $this->comment->body,
            'commented_by' => $this->comment->user->only(['id', 'name']),
        ];
    }
}

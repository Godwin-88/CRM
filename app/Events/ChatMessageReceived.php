<?php

namespace App\Events;

use App\Models\ChatSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatSession $session,
        public string $sender,
        public string $message
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat-sessions.'.$this->session->id);
    }
}

<?php

namespace App\Events;

use App\Models\Interaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewInteractionNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Interaction $interaction) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('interactions.'.$this->interaction->agent_id);
    }
}

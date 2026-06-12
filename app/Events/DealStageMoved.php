<?php

namespace App\Events;

use App\Models\Deal;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DealStageMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Deal $deal,
        public string $oldStage,
        public string $newStage,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("pipeline.{$this->deal->pipeline_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'deal_id' => $this->deal->id,
            'deal' => $this->deal->load(['account', 'contact', 'owner']),
            'old_stage' => $this->oldStage,
            'new_stage' => $this->newStage,
        ];
    }
}
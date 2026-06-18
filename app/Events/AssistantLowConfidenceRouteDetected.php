<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssistantLowConfidenceRouteDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $sessionId,
        public string $userQuery,
        public string $resolvedIntent,
        public float $confidenceScore,
    ) {}
}

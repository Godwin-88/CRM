<?php

namespace App\Listeners;

use App\Events\AssistantLowConfidenceRouteDetected;
use App\Models\AssistantLowConfidenceRoute;

class FlagAssistantLowConfidence
{
    public function handle(AssistantLowConfidenceRouteDetected $event): void
    {
        AssistantLowConfidenceRoute::create([
            'session_id' => $event->sessionId,
            'user_query' => $event->userQuery,
            'resolved_intent' => $event->resolvedIntent,
            'confidence_score' => $event->confidenceScore,
            'flagged' => true,
        ]);
    }
}

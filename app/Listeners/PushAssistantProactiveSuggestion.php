<?php

namespace App\Listeners;

use App\Events\SlaBreachWarning;
use App\Events\TicketAssigned;
use App\Helpers\AssistantProactiveHelper;

class PushAssistantProactiveSuggestion
{
    public function handle(TicketAssigned|SlaBreachWarning $event): void
    {
        $item = [
            'message' => $event->message,
        ];

        if ($event->quickReplies !== []) {
            $item['quick_replies'] = $event->quickReplies;
        }

        AssistantProactiveHelper::pushForUser($event->userId, $item);
    }
}

<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Redis;

class AssistantProactiveHelper
{
    public static function pushForUser(int $userId, array $item): void
    {
        $key = "assistant:proactive:{$userId}";
        $existing = [];
        $raw = Redis::get($key);
        if ($raw) {
            $existing = json_decode($raw, true) ?: [];
        }
        $existing[] = $item;
        Redis::setex($key, 3600, json_encode($existing));
    }

    public static function popForUser(int $userId): array
    {
        $key = "assistant:proactive:{$userId}";
        $raw = Redis::get($key);
        if (!$raw) {
            return [];
        }
        Redis::del($key);
        return json_decode($raw, true) ?: [];
    }
}

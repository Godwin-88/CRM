<?php

namespace App\Http\Middleware;

use App\Models\RateLimit;
use App\Models\SecurityEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class RateLimited
{
    public function handle(Request $request, Closure $next, string $key = 'api'): Response
    {
        $limit = $this->getRateLimit($key);

        if (! $limit) {
            return $next($request);
        }

        $identifier = $this->getIdentifier($request);
        $redisKey = "rate_limit:{$key}:{$identifier}";

        $requests = Redis::incr($redisKey);

        if ($requests === 1) {
            Redis::expire($redisKey, $limit->window_seconds);
        }

        if ($requests > $limit->max_requests) {
            $this->logAbuse($key, $identifier, $request);

            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => "Maximum {$limit->max_requests} requests per {$limit->window_seconds} seconds",
            ], 429, [
                'Retry-After' => Redis::ttl($redisKey),
            ]);
        }

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $limit->max_requests,
            'X-RateLimit-Remaining' => max(0, $limit->max_requests - $requests),
            'X-RateLimit-Reset' => Redis::ttl($redisKey),
        ]);
    }

    private function getRateLimit(string $key): ?RateLimit
    {
        return Cache::remember("rate_limit_config:{$key}", 60, fn () => RateLimit::where('key', $key)->first());
    }

    private function getIdentifier(Request $request): string
    {
        if ($request->user()) {
            return (string) $request->user()->id;
        }

        return $request->ip();
    }

    private function logAbuse(string $key, string $identifier, Request $request): void
    {
        SecurityEvent::create([
            'event_type' => 'rate_limit_abuse',
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'outcome' => 'failure',
            'metadata' => [
                'limit_key' => $key,
                'identifier' => $identifier,
            ],
        ]);
    }
}

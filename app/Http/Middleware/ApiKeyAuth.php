<?php

namespace App\Http\Middleware;

use App\Models\Integration;
use App\Models\SecurityEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): mixed
    {
        $apiKey = $request->header('X-API-Key');

        if (! $apiKey) {
            return $this->unauthorized('API key required');
        }

        $integration = Integration::where('api_key', '!=', null)
            ->get()
            ->filter(fn ($i) => decrypt($i->api_key) === $apiKey)
            ->first();

        if (! $integration || $integration->connection_status === 'revoked') {
            $this->logSecurityEvent('api_key_invalid', null, substr($apiKey, -4));

            return $this->unauthorized('Invalid API key');
        }

        // Set the integration as the "user" for this request
        $request->attributes->set('api_integration', $integration);

        return $next($request);
    }

    protected function unauthorized(string $message): Response
    {
        return response()->json([
            'error' => [
                'code' => 'unauthenticated',
                'message' => $message,
            ],
        ], 401);
    }

    protected function logSecurityEvent(string $event, $user, ?string $detail = null): void
    {
        SecurityEvent::create([
            'event_type' => $event,
            'user_id' => $user?->id,
            'email' => $user?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'outcome' => 'failed',
            'metadata' => ['detail' => $detail],
        ]);
    }
}

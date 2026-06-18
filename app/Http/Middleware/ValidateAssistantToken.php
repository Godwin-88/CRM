<?php

namespace App\Http\Middleware;

use App\Services\AssistantTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateAssistantToken
{
    public function __construct(
        protected AssistantTokenService $tokenService,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $rawToken = $request->header('X-Assistant-Token');

        if (! $rawToken) {
            return response()->json([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Assistant token required',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->tokenService->validateToken($rawToken, $request->route()->getAction('as'));

        if (! $user) {
            return response()->json([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Invalid or expired assistant token',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set('assistant_user', $user);
        $request->attributes->set('assistant_token_raw', $rawToken);

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AssistantTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AssistantTokenController extends Controller
{
    public function __construct(
        protected AssistantTokenService $tokenService,
    ) {}

    public function mint(Request $request): JsonResponse
    {
        $token = $this->tokenService->mintToken($request->user());

        return response()->json([
            'token' => Cache::get("assistant_token:{$token->id}"),
            'expires_at' => $token->expires_at->toIso8601String(),
            'session_id' => (string) Str::ulid(),
        ], 201);
    }

    public function revoke(Request $request): JsonResponse
    {
        $rawToken = $request->header('X-Assistant-Token');

        if ($rawToken) {
            $tokenHash = hash('sha256', $rawToken);
            $this->tokenService->revokeToken($tokenHash);
        }

        return response()->json([
            'message' => 'Token revoked',
        ]);
    }
}

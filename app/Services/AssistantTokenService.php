<?php

namespace App\Services;

use App\Models\AgentInternalToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AssistantTokenService
{
    public const ABILITY_ASSISTANT_CHAT = 'assistant:chat';
    public const ABILITY_ASSISTANT_TOOL_USE = 'assistant:tool.use';

    private const DEFAULT_ABILITIES = [
        self::ABILITY_ASSISTANT_CHAT,
        self::ABILITY_ASSISTANT_TOOL_USE,
    ];

    private const REQUESTABLE_ABILITIES = [
        self::ABILITY_ASSISTANT_CHAT,
        self::ABILITY_ASSISTANT_TOOL_USE,
    ];

    private const RATE_LIMIT_PER_MINUTE = 60;

    public function mintToken(User $user, array $requestedAbilities = []): AgentInternalToken
    {
        $rawToken = Str::random(64);
        $tokenHash = hash('sha256', $rawToken);

        $token = AgentInternalToken::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'abilities' => $this->normalizeAbilities($requestedAbilities),
            'expires_at' => now()->addMinutes(5),
            'used_count' => 0,
            'last_used_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::put("assistant_token:{$token->id}", $rawToken, now()->addMinutes(5));

        return $token;
    }

    private function normalizeAbilities(array $requestedAbilities): array
    {
        $abilities = self::DEFAULT_ABILITIES;

        foreach ($requestedAbilities as $ability) {
            if (! is_string($ability) || ! in_array($ability, self::REQUESTABLE_ABILITIES, true)) {
                continue;
            }

            $abilities[] = $ability;
        }

        return array_values(array_unique($abilities));
    }

    public function validateToken(string $rawToken, ?string $routeAction = null): ?User
    {
        $tokenHash = hash('sha256', $rawToken);

        $token = AgentInternalToken::where('token_hash', $tokenHash)
            ->where('expires_at', '>', now())
            ->first();

        if (! $token) {
            return null;
        }

        if ($routeAction && ! $this->tokenAllowsRoute($token, $routeAction)) {
            return null;
        }

        if (! $this->consumeRateLimit($token)) {
            $this->deleteToken($token);

            return null;
        }

        $token->increment('used_count');
        $token->update(['last_used_at' => now()]);

        if ($token->used_count > 100) {
            $this->deleteToken($token);

            return null;
        }

        return $token->user()->first();
    }

    private function consumeRateLimit(AgentInternalToken $token): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        $key = "assistant_token_rate:{$token->id}";

        try {
            $count = Cache::get($key, 0);
            $count++;
            Cache::put($key, $count, now()->addMinute());

            return $count <= self::RATE_LIMIT_PER_MINUTE;
        } catch (\Throwable $e) {
            report($e);

            return true;
        }
    }

    private function deleteToken(AgentInternalToken $token): void
    {
        Cache::forget("assistant_token:{$token->id}");
        $token->delete();
    }

    private function tokenAllowsRoute(AgentInternalToken $token, string $routeAction): bool
    {
        $abilities = $token->abilities ?: [];

        if (in_array($routeAction, $abilities, true)) {
            return true;
        }

        if (in_array(self::ABILITY_ASSISTANT_TOOL_USE, $abilities, true)) {
            return str_starts_with($routeAction, 'assistant.tool.')
                || $routeAction === 'assistant.tools.available';
        }

        return in_array(self::ABILITY_ASSISTANT_CHAT, $abilities, true)
            && in_array($routeAction, ['assistant.chat', 'assistant.chat.send'], true);
    }

    public function revokeToken(string $tokenHash): bool
    {
        $token = AgentInternalToken::where('token_hash', $tokenHash)->first();

        if (! $token) {
            return false;
        }

        Cache::forget("assistant_token:{$token->id}");
        $token->delete();

        return true;
    }

    public function revokeAllForUser(User $user): int
    {
        $tokens = AgentInternalToken::where('user_id', $user->id)->get();

        $count = 0;
        foreach ($tokens as $token) {
            Cache::forget("assistant_token:{$token->id}");
            $token->delete();
            $count++;
        }

        return $count;
    }
}

<?php

use App\Models\User;
use App\Models\AgentInternalToken;
use App\Services\AssistantTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->tokenService = app(AssistantTokenService::class);
});

it('mint token returns raw token and expires_at', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/assistant/token');

    $response->assertStatus(201)
        ->assertJsonStructure(['token', 'expires_at', 'session_id']);

    expect($response->json('token'))->not->toBeEmpty()
        ->and($response->json('expires_at'))->not->toBeEmpty();
});

it('revoke removes token from cache', function () {
    $token = $this->tokenService->mintToken($this->user, ['assistant:chat']);
    $raw = cache("assistant_token:{$token->id}");

    $response = $this->withHeader('X-Assistant-Token', $raw)
        ->deleteJson('/api/v1/assistant/token');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Token revoked']);

    expect(cache("assistant_token:{$token->id}"))->toBeNull();
});

it('token cannot be reused after revoke', function () {
    $token = $this->tokenService->mintToken($this->user, ['assistant:chat']);
    $raw = cache("assistant_token:{$token->id}");

    $this->withHeader('X-Assistant-Token', $raw)
        ->deleteJson('/api/v1/assistant/token');

    $response = $this->withHeader('X-Assistant-Token', $raw)
        ->postJson('/api/v1/assistant/tool/users/my_permissions', []);

    $response->assertStatus(401);
});

it('token expires after TTL', function () {
    $token = $this->tokenService->mintToken($this->user, ['assistant:chat']);

    $token->update(['expires_at' => now()->subMinutes(1)]);

    $raw = cache("assistant_token:{$token->id}");

    $response = $this->withHeader('X-Assistant-Token', $raw)
        ->postJson('/api/v1/assistant/tool/users/my_permissions', []);

    $response->assertStatus(401);
});

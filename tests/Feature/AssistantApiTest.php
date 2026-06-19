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

it('can mint and validate an assistant internal token', function () {
    $token = $this->tokenService->mintToken($this->user, ['assistant:chat']);
    $raw = cache("assistant_token:{$token->id}");

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Assistant-Token', $raw)
        ->postJson('/api/v1/assistant/tool/users/my_permissions', []);

    $response->assertStatus(200);
});

it('can revoke an assistant token', function () {
    $token = $this->tokenService->mintToken($this->user, ['assistant:chat']);
    $raw = cache("assistant_token:{$token->id}");

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Assistant-Token', $raw)
        ->deleteJson('/api/v1/assistant/token');

    $response->assertStatus(200);
});

it('rejects tool calls with an invalid assistant token', function () {
    $response = $this->withHeader('X-Assistant-Token', 'invalid')
        ->postJson('/api/v1/assistant/tool/contacts/search', []);

    $response->assertStatus(401);
});

it('allows a valid assistant token to call a read tool', function () {
    $token = $this->tokenService->mintToken($this->user, ['assistant:chat']);
    $raw = cache("assistant_token:{$token->id}");

    $response = $this->withHeader('X-Assistant-Token', $raw)
        ->withHeader('X-Assistant-Session', 'test-session')
        ->postJson('/api/v1/assistant/tool/users/my_permissions', []);

    $response->assertStatus(200)
        ->assertJsonStructure(['permissions', 'roles']);
});

it('blocks destructive actions via the assistant tool api', function () {
    $token = $this->tokenService->mintToken($this->user, ['assistant:chat']);
    $raw = cache("assistant_token:{$token->id}");

    $response = $this->withHeader('X-Assistant-Token', $raw)
        ->withHeader('X-Assistant-Session', 'test-session')
        ->postJson('/api/v1/assistant/tool/contacts/bulk-delete', []);

    $response->assertStatus(403)
        ->assertJsonFragment(['code' => 'action_not_permitted']);
});

it('requires confirmation for write-significant tools', function () {
    $token = $this->tokenService->mintToken($this->user, ['assistant:chat']);
    $raw = cache("assistant_token:{$token->id}");

    $response = $this->withHeader('X-Assistant-Token', $raw)
        ->withHeader('X-Assistant-Session', 'test-session')
        ->postJson('/api/v1/assistant/tool/comments/post', [
            'entity_type' => 'deal',
            'entity_id' => 'fake-uuid',
            'body' => 'This is a test comment via assistant.',
        ]);

    $response->assertStatus(412)
        ->assertJsonFragment(['requires_confirmation' => true]);
});

it('stores tool call audit logs', function () {
    $token = $this->tokenService->mintToken($this->user, ['assistant:chat']);
    $raw = cache("assistant_token:{$token->id}");

    \Illuminate\Support\Facades\Log::partialMock();

    $this->withHeader('X-Assistant-Token', $raw)
        ->withHeader('X-Assistant-Session', 'test-session')
        ->postJson('/api/v1/assistant/tool/users/my_permissions', []);

    \Illuminate\Support\Facades\Log::shouldHaveReceived('info')->with('Agent tool call');
});

it('returns available tools filtered by user permissions', function () {
    $token = $this->tokenService->mintToken($this->user, ['assistant:chat']);
    $raw = cache("assistant_token:{$token->id}");

    $response = $this->withHeader('X-Assistant-Token', $raw)
        ->getJson('/api/v1/assistant/tools/available');

    $response->assertStatus(200)
        ->assertJsonStructure(['tools' => ['name', 'tier'], 'permissions']);
});

it('proactive endpoint returns empty when no items exist', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/assistant/proactive');

    $response->assertStatus(200)
        ->assertJson(['proactive_items' => []]);
});

it('feedback endpoint stores rating', function () {
    $sessionId = (string) \Illuminate\Support\Str::ulid();
    $conversation = \App\Models\AssistantConversation::create([
        'user_id' => $this->user->id,
        'session_id' => $sessionId,
        'model_provider' => 'openai',
        'model_name' => 'gpt-4o',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/assistant/feedback', [
            'session_id' => $sessionId,
            'rating' => 5,
            'comment' => 'Great assistant!',
        ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $conversation->refresh();
    expect($conversation->feedback_positive)->toBe(1)
        ->and($conversation->feedback_negative)->toBe(0);
});

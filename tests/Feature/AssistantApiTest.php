<?php

use App\Models\User;
use App\Models\AgentInternalToken;
use App\Services\AssistantTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->user = User::factory()->create();
});

afterEach(function () {
    AgentInternalToken::query()->delete();
    \App\Models\AssistantConversation::query()->delete();
});

it('can mint and validate an assistant internal token', function () {
    $rawToken = 'test-raw-token-'.time();
    $tokenHash = hash('sha256', $rawToken);

    $token = AgentInternalToken::create([
        'id' => (string) \Illuminate\Support\Str::ulid(),
        'user_id' => $this->user->id,
        'token_hash' => $tokenHash,
        'abilities' => ['assistant:chat', 'assistant:tool.use'],
        'expires_at' => now()->addMinutes(5),
        'used_count' => 0,
        'last_used_at' => null,
    ]);

    Cache::put("assistant_token:{$token->id}", $rawToken, now()->addMinutes(5));

    $response = $this->withHeader('X-Assistant-Token', $rawToken)
        ->withHeader('X-Assistant-Session', 'test-session')
        ->postJson('/api/v1/assistant/tool/users/my_permissions', []);

    $response->assertStatus(200)
        ->assertJsonStructure(['permissions', 'roles']);
});

it('can revoke an assistant token', function () {
    $rawToken = 'test-raw-token-'.time();
    $tokenHash = hash('sha256', $rawToken);

    $token = AgentInternalToken::create([
        'id' => (string) \Illuminate\Support\Str::ulid(),
        'user_id' => $this->user->id,
        'token_hash' => $tokenHash,
        'abilities' => ['assistant:chat', 'assistant:tool.use'],
        'expires_at' => now()->addMinutes(5),
        'used_count' => 0,
        'last_used_at' => null,
    ]);

    Cache::put("assistant_token:{$token->id}", $rawToken, now()->addMinutes(5));

    $response = $this->withHeader('X-Assistant-Token', $rawToken)
        ->deleteJson('/api/v1/assistant/token');

    $response->assertStatus(200);
});

it('rejects tool calls with an invalid assistant token', function () {
    $response = $this->withHeader('X-Assistant-Token', 'invalid')
        ->postJson('/api/v1/assistant/tool/contacts/search', []);

    $response->assertStatus(401);
});

it('blocks destructive actions via the assistant tool api', function () {
    $rawToken = 'test-raw-token-'.time();
    $tokenHash = hash('sha256', $rawToken);

    $token = AgentInternalToken::create([
        'id' => (string) \Illuminate\Support\Str::ulid(),
        'user_id' => $this->user->id,
        'token_hash' => $tokenHash,
        'abilities' => ['assistant:chat', 'assistant:tool.use'],
        'expires_at' => now()->addMinutes(5),
        'used_count' => 0,
        'last_used_at' => null,
    ]);

    Cache::put("assistant_token:{$token->id}", $rawToken, now()->addMinutes(5));

    $response = $this->withHeader('X-Assistant-Token', $rawToken)
        ->withHeader('X-Assistant-Session', 'test-session')
        ->postJson('/api/v1/assistant/tool/contacts/bulk-delete', []);

    $response->assertStatus(403)
        ->assertJsonFragment(['code' => 'action_not_permitted']);
});

it('requires confirmation for write-significant tools', function () {
    $rawToken = 'test-raw-token-'.time();
    $tokenHash = hash('sha256', $rawToken);

    $token = AgentInternalToken::create([
        'id' => (string) \Illuminate\Support\Str::ulid(),
        'user_id' => $this->user->id,
        'token_hash' => $tokenHash,
        'abilities' => ['assistant:chat', 'assistant:tool.use'],
        'expires_at' => now()->addMinutes(5),
        'used_count' => 0,
        'last_used_at' => null,
    ]);

    Cache::put("assistant_token:{$token->id}", $rawToken, now()->addMinutes(5));

    $response = $this->withHeader('X-Assistant-Token', $rawToken)
        ->withHeader('X-Assistant-Session', 'test-session')
        ->postJson('/api/v1/assistant/tool/comments/post', [
            'entity_type' => 'deal',
            'entity_id' => 'fake-uuid',
            'body' => 'This is a test comment via assistant.',
        ]);

    $response->assertStatus(412)
        ->assertJsonFragment(['requires_confirmation' => true]);
});

it('requires confirmation for write-reversible tools', function () {
    $rawToken = 'test-raw-token-'.time();
    $tokenHash = hash('sha256', $rawToken);

    $token = AgentInternalToken::create([
        'id' => (string) \Illuminate\Support\Str::ulid(),
        'user_id' => $this->user->id,
        'token_hash' => $tokenHash,
        'abilities' => ['assistant:chat', 'assistant:tool.use'],
        'expires_at' => now()->addMinutes(5),
        'used_count' => 0,
        'last_used_at' => null,
    ]);

    Cache::put("assistant_token:{$token->id}", $rawToken, now()->addMinutes(5));

    $response = $this->withHeader('X-Assistant-Token', $rawToken)
        ->withHeader('X-Assistant-Session', 'test-session')
        ->postJson('/api/v1/assistant/tool/deals/move_stage', [
            'deal_id' => 'fake-uuid',
            'stage' => 'Proposal',
        ]);

    $response->assertStatus(412)
        ->assertJsonFragment(['requires_confirmation' => true])
        ->assertJsonStructure(['cascading_actions']);
});

it('returns available tools showing write-reversible requires confirmation', function () {
    $rawToken = 'test-raw-token-'.time();
    $tokenHash = hash('sha256', $rawToken);

    $token = AgentInternalToken::create([
        'id' => (string) \Illuminate\Support\Str::ulid(),
        'user_id' => $this->user->id,
        'token_hash' => $tokenHash,
        'abilities' => ['assistant:chat', 'assistant:tool.use'],
        'expires_at' => now()->addMinutes(5),
        'used_count' => 0,
        'last_used_at' => null,
    ]);

    Cache::put("assistant_token:{$token->id}", $rawToken, now()->addMinutes(5));

    $response = $this->withHeader('X-Assistant-Token', $rawToken)
        ->getJson('/api/v1/assistant/tools/available');

    $response->assertStatus(200)
        ->assertJsonStructure(['tools' => ['name', 'tier', 'requires_confirmation'], 'permissions']);

    $tools = $response->json('tools');
    $writeReversibleTool = collect($tools)->firstWhere('name', 'deals.move_stage');
    expect($writeReversibleTool['requires_confirmation'])->toBeTrue();
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

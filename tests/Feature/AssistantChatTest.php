<?php

use App\Models\User;
use App\Services\AssistantTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->tokenService = app(AssistantTokenService::class);
});

it('chat returns response from ml-service', function () {
    $token = $this->tokenService->mintToken($this->user, ['assistant:chat']);
    $raw = cache("assistant_token:{$token->id}");

    Http::fake([
        'ml-agents:8000/agents/crm/chat' => Http::response([
            'crm_response' => [
                'response' => 'Here are your deals.',
                'session_id' => '01HXYZ1234567890',
                'intent' => 'deals_list',
                'tools_to_call' => [],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Assistant-Token', $raw)
        ->withHeader('X-Assistant-Session', '01HXYZ1234567890')
        ->postJson('/api/v1/assistant/chat', [
            'message' => 'Show my deals',
        ]);

    $response->assertStatus(200)
        ->assertJsonFragment(['response' => 'Here are your deals.'])
        ->assertJsonFragment(['session_id' => '01HXYZ1234567890'])
        ->assertJsonFragment(['intent' => 'deals_list']);
});

it('chat falls back when ml-service is down', function () {
    $token = $this->tokenService->mintToken($this->user, ['assistant:chat']);
    $raw = cache("assistant_token:{$token->id}");

    Http::fake([
        'ml-agents:8000/agents/crm/chat' => Http::response('Service Unavailable', 503),
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Assistant-Token', $raw)
        ->withHeader('X-Assistant-Session', '01HXYZ1234567890')
        ->postJson('/api/v1/assistant/chat', [
            'message' => 'Show my deals',
        ]);

    $response->assertStatus(200)
        ->assertJsonFragment(['fallback' => true])
        ->assertJsonFragment(['error_code' => 'ml_service_unavailable']);
});

it('chat stores session_id on first message', function () {
    $token = $this->tokenService->mintToken($this->user, ['assistant:chat']);
    $raw = cache("assistant_token:{$token->id}");

    Http::fake([
        'ml-agents:8000/agents/crm/chat' => Http::response([
            'crm_response' => [
                'response' => 'Hello!',
                'tools_to_call' => [],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Assistant-Token', $raw)
        ->postJson('/api/v1/assistant/chat', [
            'message' => 'Hello',
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['session_id'])
        ->assertJsonFragment(['response' => 'Hello!']);

    expect($response->json('session_id'))->not->toBeEmpty();
});

it('chat passes confirmed_actions through', function () {
    $token = $this->tokenService->mintToken($this->user, ['assistant:chat']);
    $raw = cache("assistant_token:{$token->id}");

    Http::fake(function ($request) {
        return Http::response([
            'crm_response' => [
                'response' => 'Action executed.',
                'tools_to_call' => [],
            ],
        ], 200);
    });

    $response = $this->actingAs($this->user, 'sanctum')
        ->withHeader('X-Assistant-Token', $raw)
        ->withHeader('X-Assistant-Session', '01HXYZ1234567890')
        ->postJson('/api/v1/assistant/chat', [
            'message' => 'Execute the confirmed action',
            'confirmed_actions' => [
                [
                    'tool' => 'contacts/post',
                    'arguments' => ['name' => 'John Doe'],
                ],
            ],
        ]);

    $response->assertStatus(200);

    Http::assertSent(function ($request) {
        return $request->url() === 'http://ml-agents:8000/agents/crm/chat'
            && $request->hasHeader('X-Assistant-Token')
            && isset($request['confirmed_actions'][0]['tool'])
            && $request['confirmed_actions'][0]['tool'] === 'contacts/post'
            && $request['confirmed_actions'][0]['arguments']['name'] === 'John Doe';
    });
});

<?php

use App\Models\User;
use App\Services\AssistantTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->tokenService = app(AssistantTokenService::class);
});

it('proactive returns items when redis has data', function () {
    $key = "assistant:proactive:{$this->user->id}";
    $items = json_encode([
        ['type' => 'deal', 'message' => 'Follow up on deal #123'],
    ]);

    \Illuminate\Support\Facades\Redis::setex($key, 3600, $items);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/assistant/proactive');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'proactive_items')
        ->assertJsonFragment(['type' => 'deal']);
});

it('proactive clears items after reading', function () {
    $key = "assistant:proactive:{$this->user->id}";
    $items = json_encode([
        ['type' => 'deal', 'message' => 'Follow up on deal #123'],
    ]);

    \Illuminate\Support\Facades\Redis::setex($key, 3600, $items);

    $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/assistant/proactive');

    expect(\Illuminate\Support\Facades\Redis::get($key))->toBeNull();
});

it('proactive returns empty when no items', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/assistant/proactive');

    $response->assertStatus(200)
        ->assertJson(['proactive_items' => []]);
});

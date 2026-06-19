<?php

use App\Models\User;
use App\Models\AssistantConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('feedback updates existing conversation rating', function () {
    $conversation = AssistantConversation::create([
        'id' => (string) \Illuminate\Support\Str::ulid(),
        'user_id' => $this->user->id,
        'session_id' => '01HXYZ1234567890',
        'model_provider' => 'openai',
        'model_name' => 'gpt-4o',
        'started_at' => now(),
        'feedback_positive' => 0,
        'feedback_negative' => 0,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/assistant/feedback', [
            'session_id' => '01HXYZ1234567890',
            'rating' => 4,
            'comment' => 'Good response.',
        ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $conversation->refresh();

    expect($conversation->feedback_positive)->toBe(1)
        ->and($conversation->feedback_negative)->toBe(0)
        ->and($conversation->feedback_comment)->toBe('Good response.');
});

it('feedback creates conversation if not exists', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/assistant/feedback', [
            'session_id' => '01HXYZ1234567890',
            'rating' => 2,
            'comment' => 'Not helpful.',
        ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $conversation = AssistantConversation::where('session_id', '01HXYZ1234567890')->first();

    expect($conversation)->not->toBeNull()
        ->and($conversation->feedback_positive)->toBe(0)
        ->and($conversation->feedback_negative)->toBe(1)
        ->and($conversation->feedback_comment)->toBe('Not helpful.');
});

it('feedback stores comment', function () {
    AssistantConversation::create([
        'id' => (string) \Illuminate\Support\Str::ulid(),
        'user_id' => $this->user->id,
        'session_id' => '01HXYZ1234567890',
        'model_provider' => 'openai',
        'model_name' => 'gpt-4o',
        'started_at' => now(),
        'feedback_positive' => 0,
        'feedback_negative' => 0,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/assistant/feedback', [
            'session_id' => '01HXYZ1234567890',
            'rating' => 5,
            'comment' => 'Excellent assistant!',
        ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $conversation = AssistantConversation::where('session_id', '01HXYZ1234567890')->first();

    expect($conversation->feedback_comment)->toBe('Excellent assistant!')
        ->and($conversation->feedback_positive)->toBe(1);
});

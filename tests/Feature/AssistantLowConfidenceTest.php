<?php

use App\Models\User;
use App\Models\AssistantLowConfidenceRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can create low-confidence route record', function () {
    $route = AssistantLowConfidenceRoute::create([
        'session_id' => '01HXYZ1234567890',
        'user_query' => 'What is my pipeline value?',
        'resolved_intent' => 'analytics.pipeline',
        'confidence_score' => 0.42,
        'flagged' => true,
    ]);

    expect($route->exists)->toBeTrue()
        ->and($route->confidence_score)->toBe(0.42)
        ->and($route->flagged)->toBeTrue();
});

it('scope by session_id works', function () {
    AssistantLowConfidenceRoute::create([
        'session_id' => '01HXYZ1111111111',
        'user_query' => 'Query one',
        'resolved_intent' => 'intent_a',
        'confidence_score' => 0.5,
        'flagged' => true,
    ]);

    AssistantLowConfidenceRoute::create([
        'session_id' => '01HXYZ2222222222',
        'user_query' => 'Query two',
        'resolved_intent' => 'intent_b',
        'confidence_score' => 0.6,
        'flagged' => false,
    ]);

    $routes = AssistantLowConfidenceRoute::where('session_id', '01HXYZ1111111111')->get();

    expect($routes)->toHaveCount(1)
        ->and($routes->first()->resolved_intent)->toBe('intent_a');
});

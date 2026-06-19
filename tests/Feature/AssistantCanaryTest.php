<?php

use App\Helpers\AssistantPromptVersioning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    File::put(resource_path('prompts/assistant/versions.json'), json_encode([
        [
            'version' => 'v1',
            'label' => 'v1',
            'system_prompt' => 'Prompt v1',
            'canary_percentage' => 100,
            'created_at' => '2026-06-18T00:00:00Z',
        ],
        [
            'version' => 'v2',
            'label' => 'v2-canary',
            'system_prompt' => 'Prompt v2',
            'canary_percentage' => 10,
            'created_at' => '2026-06-18T00:00:00Z',
        ],
    ], JSON_PRETTY_PRINT));
});

it('canary returns current version by default', function () {
    $result = AssistantPromptVersioning::canary();

    expect($result)->toHaveKey('version')
        ->and($result)->toHaveKey('label')
        ->and($result['version'])->toBe('v1')
        ->and($result['label'])->toBe('v1')
        ->and($result['canary_percentage'])->toBe(10.0);
});

it('canary respects percentage parameter', function () {
    $result = AssistantPromptVersioning::canary(50.0);

    expect($result['version'])->toBe('v1')
        ->and($result['canary_percentage'])->toBe(50.0);
});

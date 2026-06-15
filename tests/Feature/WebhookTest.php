<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Webhook;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_webhook_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/webhooks', [
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['contact.created'],
        ]);

        $response->assertUnauthorized();
    }

    public function test_signing_secret_generated(): void
    {
        $admin = User::factory()->create();

        $webhook = Webhook::create([
            'name' => 'Test',
            'url' => 'https://example.com',
            'events' => ['test'],
            'signing_secret' => 'whsec_'.str()->random(32),
            'created_by' => $admin->id,
        ]);

        $this->assertStringStartsWith('whsec_', $webhook->signing_secret);
        $this->assertEquals(38, strlen($webhook->signing_secret));
    }
}
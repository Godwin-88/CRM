<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MfaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_unauthenticated_user_redirected_from_mfa_verify(): void
    {
        $response = $this->get(route('mfa.verify'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_mfa_redirected_to_setup(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('mfa.verify'));

        $response->assertRedirect(route('mfa.setup'));
    }

    public function test_admin_can_access_mfa_setup(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user);

        $response = $this->get(route('mfa.setup'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page->component('Auth/MfaSetup'));
    }
}

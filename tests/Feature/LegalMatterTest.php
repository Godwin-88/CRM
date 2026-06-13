<?php

namespace Tests\Feature;

use App\Models\LegalMatter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegalMatterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');

        $this->agent = User::factory()->create();
        $this->agent->assignRole('agent');
    }

    public function test_admin_can_view_legal_matters_index(): void
    {
        $this->actingAs($this->admin);

        LegalMatter::factory()->count(3)->create();

        $response = $this->get('/legal');

        $response->assertStatus(200);
        $response->assertInertia('Legal/Index');
    }

    public function test_manager_can_view_legal_matters_index(): void
    {
        $this->actingAs($this->manager);

        $response = $this->get('/legal');

        $response->assertStatus(200);
    }

    public function test_agent_cannot_view_legal_matters_index(): void
    {
        $this->actingAs($this->agent);

        $response = $this->get('/legal');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_legal_matter_show(): void
    {
        $this->actingAs($this->admin);

        $matter = LegalMatter::factory()->create();

        $response = $this->get("/legal/{$matter->id}");

        $response->assertStatus(200);
        $response->assertInertia('Legal/Show');
    }

    public function test_admin_can_create_legal_matter(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/legal', [
            'subject' => 'Test Matter',
            'description' => 'Description',
            'status' => 'open',
            'type' => 'advisory',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('legal_matters', [
            'subject' => 'Test Matter',
        ]);
    }

    public function test_admin_can_update_legal_matter(): void
    {
        $this->actingAs($this->admin);

        $matter = LegalMatter::factory()->create([
            'subject' => 'Original Subject',
        ]);

        $response = $this->put("/legal/{$matter->id}", [
            'subject' => 'Updated Subject',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('legal_matters', [
            'id' => $matter->id,
            'subject' => 'Updated Subject',
        ]);
    }

    public function test_admin_can_delete_legal_matter(): void
    {
        $this->actingAs($this->admin);

        $matter = LegalMatter::factory()->create();

        $response = $this->delete("/legal/{$matter->id}");

        $response->assertRedirect('/legal');
        $this->assertSoftDeleted('legal_matters', [
            'id' => $matter->id,
        ]);
    }

    public function test_admin_can_restore_legal_matter(): void
    {
        $this->actingAs($this->admin);

        $matter = LegalMatter::factory()->create();
        $matter->delete();

        $response = $this->post("/legal/{$matter->id}/restore");

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('legal_matters', [
            'id' => $matter->id,
        ]);
    }

    public function test_admin_can_add_note_to_legal_matter(): void
    {
        $this->actingAs($this->admin);

        $matter = LegalMatter::factory()->create();

        $response = $this->post("/legal/{$matter->id}/notes", [
            'body' => 'Test note body',
            'type' => 'note',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('legal_matter_notes', [
            'legal_matter_id' => $matter->id,
            'body' => 'Test note body',
        ]);
    }

    public function test_unauthenticated_user_cannot_view_legal_matters(): void
    {
        $response = $this->get('/legal');

        $response->assertRedirect('/login');
    }
}

<?php

namespace Tests\Feature;

use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContractTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');

        $this->agent = User::factory()->create();
        $this->agent->assignRole('agent');
    }

    public function test_admin_can_view_contract_template_index(): void
    {
        $this->actingAs($this->admin);

        ContractTemplate::factory()->count(3)->create();

        $response = $this->get('/admin/contract-templates');

        $response->assertStatus(200);
        $response->assertInertia('Admin/ContractTemplates/Index');
    }

    public function test_manager_can_view_contract_template_index(): void
    {
        $this->actingAs($this->manager);

        $response = $this->get('/admin/contract-templates');

        $response->assertStatus(200);
    }

    public function test_agent_cannot_view_contract_template_index(): void
    {
        $this->actingAs($this->agent);

        $response = $this->get('/admin/contract-templates');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_contract_template(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/contract-templates/create');

        $response->assertStatus(200);
        $response->assertInertia('Admin/ContractTemplates/Create');
    }

    public function test_admin_can_store_contract_template(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/contract-templates', [
            'name' => 'Test Template',
            'description' => 'Test description',
            'type' => 'msa',
            'clauses' => [],
        ]);

        $response->assertRedirect('/admin/contract-templates');
        $this->assertDatabaseHas('contract_templates', [
            'name' => 'Test Template',
        ]);
    }

    public function test_admin_can_edit_contract_template(): void
    {
        $this->actingAs($this->admin);

        $template = ContractTemplate::factory()->create();

        $response = $this->get("/admin/contract-templates/{$template->id}/edit");

        $response->assertStatus(200);
        $response->assertInertia('Admin/ContractTemplates/Edit');
    }

    public function test_admin_can_update_contract_template(): void
    {
        $this->actingAs($this->admin);

        $template = ContractTemplate::factory()->create([
            'name' => 'Original Name',
        ]);

        $response = $this->put("/admin/contract-templates/{$template->id}", [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'type' => $template->type,
            'clauses' => [],
        ]);

        $response->assertRedirect('/admin/contract-templates');
        $this->assertDatabaseHas('contract_templates', [
            'id' => $template->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_admin_can_delete_contract_template(): void
    {
        $this->actingAs($this->admin);

        $template = ContractTemplate::factory()->create();

        $response = $this->delete("/admin/contract-templates/{$template->id}");

        $response->assertSessionHas('success');
        $this->assertSoftDeleted('contract_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_admin_can_restore_contract_template(): void
    {
        $this->actingAs($this->admin);

        $template = ContractTemplate::factory()->create();
        $template->delete();

        $response = $this->post("/admin/contract-templates/{$template->id}/restore");

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('contract_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_view_contract_templates(): void
    {
        $response = $this->get('/admin/contract-templates');

        $response->assertRedirect('/login');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('r2');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');

        $this->agent = User::factory()->create();
        $this->agent->assignRole('agent');
    }

    public function test_admin_can_view_contracts_index(): void
    {
        $this->actingAs($this->admin);

        Contract::factory()->count(3)->create();

        $response = $this->get('/contracts');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Contracts/Index'));
    }

    public function test_admin_can_view_contract_show(): void
    {
        $this->actingAs($this->admin);

        $contract = Contract::factory()->create();

        $response = $this->get("/contracts/{$contract->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Contracts/Show'));
    }

    public function test_admin_can_create_contract(): void
    {
        $this->actingAs($this->admin);

        $account = Account::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->post('/contracts', [
            'title' => 'Test Contract',
            'type' => 'msa',
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'value' => 10000,
            'currency' => 'USD',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contracts', [
            'title' => 'Test Contract',
            'type' => 'msa',
        ]);
    }

    public function test_admin_can_update_contract(): void
    {
        $this->actingAs($this->admin);

        $contract = Contract::factory()->create([
            'title' => 'Original Title',
        ]);

        $response = $this->put("/contracts/{$contract->id}", [
            'title' => 'Updated Title',
            'type' => $contract->type,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_admin_can_delete_contract(): void
    {
        $this->actingAs($this->admin);

        $contract = Contract::factory()->create();

        $response = $this->delete("/contracts/{$contract->id}");

        $response->assertRedirect('/contracts');
        $this->assertSoftDeleted('contracts', [
            'id' => $contract->id,
        ]);
    }

    public function test_manager_can_view_contracts_index(): void
    {
        $this->actingAs($this->manager);

        $response = $this->get('/contracts');

        $response->assertStatus(200);
    }

    public function test_agent_can_view_contracts_index(): void
    {
        $this->actingAs($this->agent);

        $response = $this->get('/contracts');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_view_contracts(): void
    {
        $response = $this->get('/contracts');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_duplicate_contract(): void
    {
        $this->actingAs($this->admin);

        $contract = Contract::factory()->create([
            'title' => 'Original',
            'status' => Contract::STATUS_ACTIVE,
        ]);

        $response = $this->post("/contracts/{$contract->id}/duplicate");

        $response->assertRedirect();
        $this->assertDatabaseHas('contracts', [
            'title' => 'Original (Copy)',
            'status' => Contract::STATUS_DRAFT,
        ]);
    }

    public function test_admin_can_regenerate_contract(): void
    {
        $this->actingAs($this->admin);

        $contract = Contract::factory()->create([
            'current_version' => 1,
        ]);

        $response = $this->post("/contracts/{$contract->id}/regenerate");

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'current_version' => 2,
        ]);
    }

    public function test_download_signed_url_returns_redirect(): void
    {
        $this->actingAs($this->admin);

        $contract = Contract::factory()->create();
        $contract->update([
            'document_path' => 'contracts/'.$contract->id.'/v1.pdf',
        ]);

        Storage::disk('r2')->put('contracts/'.$contract->id.'/v1.pdf', 'dummy pdf');

        $response = $this->get("/contracts/{$contract->id}/download");

        $response->assertStatus(302);
    }

    public function test_api_index_returns_contracts(): void
    {
        $this->actingAs($this->admin);

        Contract::factory()->count(3)->create();

        $response = $this->get('/api/v1/contracts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'status',
                    'type',
                ],
            ],
        ]);
    }
}

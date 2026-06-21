<?php

use App\Models\CaseRecord;
use App\Models\Contact;
use App\Models\FormSchema;
use App\Models\FormSchemaVersion;
use App\Models\ServiceCatalogItem;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Factories\ServiceRequestFactory;

it('manages service catalog items', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->postJson('/api/v1/service-catalog-items', [
        'name' => 'Onboarding Support',
        'slug' => 'onboarding-support',
        'description' => 'Customer onboarding support',
        'default_priority' => 'medium',
        'portal_visible' => true,
        'api_visible' => true,
        'fields' => [
            ['name' => 'details', 'label' => 'Details', 'type' => 'textarea', 'required' => true],
        ],
    ]);

    $response->assertCreated();
    $catalogItemId = $response->json('id');

    $this->actingAs($admin)->putJson("/api/v1/service-catalog-items/{$catalogItemId}", [
        'description' => 'Updated onboarding support',
    ])->assertOk();

    $this->actingAs($admin)->deleteJson("/api/v1/service-catalog-items/{$catalogItemId}")
        ->assertOk();

    $this->assertFalse(ServiceCatalogItem::find($catalogItemId)->is_active);
});

it('validates required service request intake fields', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $schema = FormSchema::factory()->create();
    $version = FormSchemaVersion::factory()->create(['form_schema_id' => $schema->id]);
    $catalogItem = ServiceCatalogItem::factory()->create(['intake_form_schema_id' => $schema->id]);
    $contact = Contact::factory()->create();

    $this->actingAs($admin)->postJson('/api/v1/service-requests', [
        'catalog_item_id' => $catalogItem->id,
        'requester_id' => $admin->id,
        'contact_id' => $contact->id,
        'channel' => 'api',
        'form_response' => [],
    ])->assertUnprocessable();

    $response = $this->actingAs($admin)->postJson('/api/v1/service-requests', [
        'catalog_item_id' => $catalogItem->id,
        'requester_id' => $admin->id,
        'contact_id' => $contact->id,
        'channel' => 'api',
        'form_response' => [
            'issue_description' => 'Needs help',
        ],
    ]);

    $response->assertCreated();

    $serviceRequest = ServiceRequest::find($response->json('id'));
    $this->assertSame($version->id, $serviceRequest->catalog_item_version_id);
    $this->assertDatabaseHas('service_request_status_history', [
        'service_request_id' => $serviceRequest->id,
        'to_status' => ServiceRequest::STATUS_SUBMITTED,
    ]);
});

it('completes a service request with a closure reason', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $catalogItem = ServiceCatalogItem::factory()->create();
    $contact = Contact::factory()->create();
    $serviceRequest = ServiceRequestFactory::create([
        'catalog_item_id' => $catalogItem->id,
        'requester_id' => $admin->id,
        'contact_id' => $contact->id,
        'assigned_to' => $admin->id,
    ]);

    $this->actingAs($admin)->postJson("/api/v1/service-requests/{$serviceRequest->id}/complete", [])
        ->assertUnprocessable();

    $this->actingAs($admin)->postJson("/api/v1/service-requests/{$serviceRequest->id}/complete", [
        'closure_reason' => 'Work completed',
    ])->assertOk();

    $this->assertSame(ServiceRequest::STATUS_COMPLETED, $serviceRequest->fresh()->status);
    $this->assertDatabaseHas('service_request_status_history', [
        'service_request_id' => $serviceRequest->id,
        'to_status' => ServiceRequest::STATUS_COMPLETED,
    ]);
});

it('blocks case closure until sign-off is approved', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $case = CaseRecord::factory()->pendingSignoff()->create(['owner_id' => $admin->id]);

    $this->actingAs($admin)->postJson("/api/v1/cases/{$case->id}/close", [
        'closure_summary' => 'Done',
    ])->assertUnprocessable();

    $this->actingAs($admin)->postJson("/api/v1/cases/{$case->id}/signoff/approve", [
        'reason' => 'Approved',
    ])->assertOk();

    $this->actingAs($admin)->postJson("/api/v1/cases/{$case->id}/close", [
        'closure_summary' => 'Done',
    ])->assertOk();

    $this->assertSame(CaseRecord::STATUS_CLOSED, $case->fresh()->status);
});

it('links related records to cases', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $case = CaseRecord::factory()->create(['owner_id' => $admin->id]);
    $contact = Contact::factory()->create();

    $response = $this->actingAs($admin)->postJson("/api/v1/cases/{$case->id}/links", [
        'linkable_type' => Contact::class,
        'linkable_id' => $contact->id,
        'link_type' => 'primary_contact',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('case_links', [
        'case_record_id' => $case->id,
        'linkable_type' => Contact::class,
        'linkable_id' => $contact->id,
    ]);
});

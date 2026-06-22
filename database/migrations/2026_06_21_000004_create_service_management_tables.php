<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_schemas', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('owner_type')->nullable();
            $table->foreignUlid('owner_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignUlid('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
        });

        Schema::create('form_schema_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('form_schema_id')->constrained('form_schemas')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('fields');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['form_schema_id', 'version_number']);
        });

        Schema::create('form_responses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('form_schema_version_id')->constrained('form_schema_versions')->cascadeOnDelete();
            $table->string('formable_type');
            $table->foreignUlid('formable_id');
            $table->foreignUlid('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('response_data');
            $table->json('field_snapshots');
            $table->timestamps();

            $table->index(['formable_type', 'formable_id']);
            $table->unique(['formable_type', 'formable_id'], 'form_responses_formable_unique');
        });

        Schema::create('service_catalog_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignUlid('category_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
            $table->text('description')->nullable();
            $table->text('customer_instructions')->nullable();
            $table->string('default_priority')->default('medium');
            $table->foreignUlid('default_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('default_owner_role')->nullable();
            $table->foreignUlid('sla_policy_id')->nullable()->constrained('sla_definitions')->nullOnDelete();
            $table->foreignUlid('intake_form_schema_id')->nullable()->constrained('form_schemas')->nullOnDelete();
            $table->json('required_documents')->nullable();
            $table->json('automation_config')->nullable();
            $table->boolean('portal_visible')->default(true);
            $table->boolean('email_visible')->default(false);
            $table->boolean('kiosk_visible')->default(false);
            $table->boolean('api_visible')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_agent_only')->default(false);
            $table->foreignUlid('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'is_active']);
            $table->index(['portal_visible', 'is_active']);
            $table->index(['default_team_id', 'is_active']);
            $table->index(['sla_policy_id']);
        });

        Schema::create('service_catalog_item_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('service_catalog_item_id')->constrained('service_catalog_items')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('fields');
            $table->json('required_documents')->nullable();
            $table->json('automation_config')->nullable();
            $table->text('customer_instructions')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['service_catalog_item_id', 'version_number']);
        });

        Schema::create('case_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('case_number')->unique();
            $table->string('title');
            $table->string('type')->default('service_delivery');
            $table->string('priority')->default('medium');
            $table->string('status')->default('new');
            $table->foreignUlid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('primary_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignUlid('primary_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignUlid('sla_instance_id')->nullable()->constrained('sla_instances')->nullOnDelete();
            $table->foreignUlid('closure_report_id')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('resolution_details')->nullable();
            $table->text('closure_summary')->nullable();
            $table->boolean('signoff_required')->default(false);
            $table->string('signoff_status')->nullable();
            $table->timestamp('signoff_due_at')->nullable();
            $table->foreignUlid('signoff_approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signoff_approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['owner_id', 'status']);
            $table->index(['primary_account_id', 'status']);
            $table->index(['primary_contact_id', 'status']);
            $table->index(['type', 'status']);
        });

        Schema::create('case_links', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('case_record_id')->constrained('case_records')->cascadeOnDelete();
            $table->string('linkable_type');
            $table->foreignUlid('linkable_id');
            $table->string('link_type')->default('related');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['linkable_type', 'linkable_id']);
            $table->index(['case_record_id', 'link_type']);
        });

        Schema::create('case_status_history', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('case_record_id')->constrained('case_records')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignUlid('transitioned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['case_record_id', 'created_at']);
        });

        Schema::create('case_closure_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('case_record_id')->constrained('case_records')->cascadeOnDelete();
            $table->foreignUlid('prepared_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('summary');
            $table->text('root_cause')->nullable();
            $table->text('resolution_details')->nullable();
            $table->text('customer_facing_summary')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['case_record_id', 'status']);
        });

        Schema::create('case_signoffs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('case_record_id')->constrained('case_records')->cascadeOnDelete();
            $table->foreignUlid('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('rejected_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['case_record_id', 'status']);
        });

        Schema::table('case_records', function (Blueprint $table) {
            $table->foreign('closure_report_id')->references('id')->on('case_closure_reports')->nullOnDelete();
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('catalog_item_id')->constrained('service_catalog_items')->cascadeOnDelete();
            $table->foreignUlid('catalog_item_version_id')->nullable()->constrained('service_catalog_item_versions')->nullOnDelete();
            $table->foreignUlid('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignUlid('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('channel');
            $table->string('source_identifier')->nullable();
            $table->string('status')->default('submitted');
            $table->string('priority')->default('medium');
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignUlid('sla_instance_id')->nullable()->constrained('sla_instances')->nullOnDelete();
            $table->foreignUlid('form_response_id')->nullable()->constrained('form_responses')->nullOnDelete();
            $table->foreignUlid('case_record_id')->nullable()->constrained('case_records')->nullOnDelete();
            $table->string('closure_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_reason')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('source_identifier');
            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'status']);
            $table->index(['team_id', 'status']);
            $table->index(['account_id', 'status']);
            $table->index(['contact_id', 'status']);
            $table->index(['catalog_item_id', 'status']);
        });

        Schema::create('service_request_status_history', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('service_request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignUlid('transitioned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['service_request_id', 'created_at']);
        });

        Schema::create('service_request_links', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('service_request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->foreignUlid('linked_service_request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->string('link_type')->default('related');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['service_request_id', 'linked_service_request_id', 'link_type']);
        });

        Schema::create('service_request_document_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('service_request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('required_by')->nullable();
            $table->string('status')->default('requested');
            $table->foreignUlid('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fulfilled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['service_request_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_document_requests');
        Schema::dropIfExists('service_request_links');
        Schema::dropIfExists('service_request_status_history');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('case_signoffs');
        Schema::dropIfExists('case_closure_reports');
        Schema::dropIfExists('case_status_history');
        Schema::dropIfExists('case_links');
        Schema::dropIfExists('case_records');
        Schema::dropIfExists('service_catalog_item_versions');
        Schema::dropIfExists('service_catalog_items');
        Schema::dropIfExists('form_responses');
        Schema::dropIfExists('form_schema_versions');
        Schema::dropIfExists('form_schemas');
    }
};

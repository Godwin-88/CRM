<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignUlid('account_id')->nullable()->constrained('accounts')->after('contact_id');
            $table->string('type')->nullable()->after('account_id');
            $table->decimal('value', 15, 2)->nullable()->after('status');
            $table->string('currency', 3)->default('USD')->after('value');
            $table->date('start_date')->nullable()->after('currency');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('document_path')->nullable()->after('end_date');
            $table->string('e_signature_status')->nullable()->after('document_path');
            $table->foreignUlid('template_id')->nullable()->constrained('contract_templates')->after('e_signature_status');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->after('template_id');
            $table->foreignUlid('account_manager_id')->nullable()->constrained('users')->after('created_by');
            $table->boolean('suppress_reminders')->default(false)->after('account_manager_id');
            $table->integer('current_version')->default(1)->after('suppress_reminders');
            $table->timestamp('activated_at')->nullable()->after('current_version');
            $table->timestamp('terminated_at')->nullable()->after('activated_at');
            $table->text('termination_reason')->nullable()->after('terminated_at');
            $table->jsonb('custom_variables')->nullable()->after('termination_reason');
        });

        Schema::create('contract_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contract_id')->constrained()->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('status');
            $table->string('document_path')->nullable();
            $table->integer('page_count')->nullable();
            $table->integer('file_size')->nullable();
            $table->foreignUlid('created_by')->constrained('users');
            $table->jsonb('variables')->nullable();
            $table->jsonb('selected_clauses')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_signatories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('contract_version_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('users');
            $table->foreignUlid('contact_id')->nullable()->constrained('contacts');
            $table->string('name');
            $table->string('email');
            $table->string('role')->nullable();
            $table->string('status')->default('pending');
            $table->string('provider')->nullable();
            $table->string('external_envelope_id')->nullable();
            $table->string('signing_token')->nullable();
            $table->text('signing_url')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->text('decline_reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->integer('signing_order')->default(0);
            $table->boolean('is_sequential')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_milestones', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contract_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->string('assigned_party')->nullable();
            $table->string('status')->default('pending');
            $table->nullableMorphs('assigned_to');
            $table->date('completed_at')->nullable();
            $table->text('completion_note')->nullable();
            $table->boolean('is_notified')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_kpi_fields', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('contract_type')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');
            $table->boolean('is_required')->default(false);
            $table->jsonb('settings')->nullable();
            $table->jsonb('options')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contract_kpi_values', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('contract_kpi_field_id')->constrained('contract_kpi_fields');
            $table->jsonb('value');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->foreignUlid('recorded_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_kpi_values');
        Schema::dropIfExists('contract_kpi_fields');
        Schema::dropIfExists('contract_milestones');
        Schema::dropIfExists('contract_signatories');
        Schema::dropIfExists('contract_versions');
        
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['template_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['account_manager_id']);
            
            $table->dropColumn([
                'account_id', 'type', 'value', 'currency', 'start_date', 'end_date',
                'document_path', 'e_signature_status', 'template_id', 'created_by',
                'account_manager_id', 'suppress_reminders', 'current_version',
                'activated_at', 'terminated_at', 'termination_reason', 'custom_variables'
            ]);
        });
    }
};

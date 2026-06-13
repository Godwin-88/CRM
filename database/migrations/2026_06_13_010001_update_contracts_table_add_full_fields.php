<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignUlid('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('type')->nullable(); // msa|nda|sla|renewal|upsell|custom
            $table->string('status')->default('draft')->index(); // draft|sent|signed|active|expiring|expired|declined|terminated
            $table->decimal('value', 15, 2)->nullable();
            $table->string('currency')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable()->index();
            $table->string('document_path')->nullable();
            $table->string('e_signature_status')->nullable(); // pending|sent|signed|declined
            $table->foreignUlid('template_id')->nullable()->constrained('contract_templates')->nullOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('account_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('legal_matter_id')->nullable()->constrained('legal_matters')->nullOnDelete();
            $table->boolean('suppress_reminders')->default(false);
            $table->integer('current_version')->default(1);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->text('termination_reason')->nullable();
            $table->json('custom_variables')->nullable();
            $table->softDeletes();
            $table->index(['type', 'status']);
            $table->index(['account_manager_id', 'end_date']);
            $table->index(['account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropForeign(['template_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['account_manager_id']);
            $table->dropForeign(['legal_matter_id']);
            $table->dropColumn([
                'account_id', 'type', 'status', 'value', 'currency',
                'start_date', 'end_date', 'document_path', 'e_signature_status',
                'template_id', 'created_by', 'account_manager_id', 'legal_matter_id',
                'suppress_reminders', 'current_version', 'activated_at',
                'terminated_at', 'termination_reason', 'custom_variables',
            ]);
        });
    }
};

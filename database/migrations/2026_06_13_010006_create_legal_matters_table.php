<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_matters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('status')->default('open')->index(); // open|in_progress|pending_external|resolved|closed
            $table->string('type')->nullable()->index(); // dispute|correspondence|regulatory|advisory|custom
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignUlid('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'assigned_to']);
            $table->index(['account_id']);
            $table->index(['contact_id']);
        });

        Schema::create('legal_matter_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('legal_matter_id')->constrained('legal_matters')->cascadeOnDelete();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->string('type')->default('note'); // note|update|resolution
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['legal_matter_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_matter_notes');
        Schema::dropIfExists('legal_matters');
    }
};

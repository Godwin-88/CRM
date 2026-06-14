<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('legal_matters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('status')->default('open');
            $table->string('type')->default('advisory');
            $table->foreignUlid('assigned_to')->nullable()->constrained('users');
            $table->foreignUlid('account_id')->nullable()->constrained('accounts');
            $table->foreignUlid('contact_id')->nullable()->constrained('contacts');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('legal_matter_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('legal_matter_id')->constrained('legal_matters')->onDelete('cascade');
            $table->foreignUlid('created_by')->constrained('users');
            $table->text('body');
            $table->string('type')->default('note');
            $table->jsonb('attachments')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        // Add legal_matter_id to contracts table
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignUlid('legal_matter_id')->nullable()->constrained('legal_matters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['legal_matter_id']);
            $table->dropColumn('legal_matter_id');
        });
        Schema::dropIfExists('legal_matter_notes');
        Schema::dropIfExists('legal_matters');
    }
};

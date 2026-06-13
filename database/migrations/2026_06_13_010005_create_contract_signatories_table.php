<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_signatories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignUlid('contract_version_id')->nullable()->constrained('contract_versions')->nullOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('role')->nullable(); // counterparty|witness|approver
            $table->string('status')->default('pending')->index(); // pending|viewed|signed|declined
            $table->string('provider')->nullable(); // internal|docusign|hellosign
            $table->string('external_envelope_id')->nullable()->index();
            $table->string('signing_token')->nullable()->unique();
            $table->string('signing_url')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->text('decline_reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->integer('signing_order')->nullable();
            $table->boolean('is_sequential')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contract_id', 'status']);
            $table->index(['external_envelope_id']);
            $table->index(['signing_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_signatories');
    }
};

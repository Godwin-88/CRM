<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_milestones', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('assigned_party')->nullable(); // ours|counterparty
            $table->string('status')->default('pending')->index(); // pending|completed|missed
            $table->string('assigned_to_type')->nullable(); // user
            $table->foreignUlid('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('completed_at')->nullable();
            $table->text('completion_note')->nullable();
            $table->boolean('is_notified')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contract_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_milestones');
    }
};

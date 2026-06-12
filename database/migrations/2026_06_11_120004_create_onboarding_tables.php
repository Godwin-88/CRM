<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('onboarding_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('steps'); // ordered array of steps
            $table->foreignUlid('created_by')->constrained('users');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('onboarding_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('template_id')->constrained('onboarding_templates');
            $table->foreignUlid('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignUlid('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('status')->default('in_progress'); // in_progress | completed | cancelled
            $table->integer('percentage_complete')->default(0);
            $table->timestamp('enrolled_at');
            $table->timestamp('completed_at')->nullable();
            $table->foreignUlid('enroled_by')->constrained('users');
            $table->timestamps();

            $table->index(['contact_id', 'status']);
            $table->index(['account_id', 'status']);
        });

        Schema::create('onboarding_activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('record_id')->constrained('onboarding_records')->cascadeOnDelete();
            $table->foreignUlid('template_step_id')->nullable(); // reference to step index in JSON
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('assigned_role'); // account_manager | technical_support etc
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue'])->default('pending');
            $table->text('completion_note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['record_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_activities');
        Schema::dropIfExists('onboarding_records');
        Schema::dropIfExists('onboarding_templates');
    }
};

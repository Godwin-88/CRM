<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sla_definitions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('support_category')->nullable();
            $table->foreignUlid('loyalty_tier_id')->nullable()->constrained('loyalty_tiers')->nullOnDelete();
            $table->string('account_type')->nullable(); // e.g. customer | partner
            $table->unsignedInteger('first_response_time_business_hours')->nullable();
            $table->unsignedInteger('resolution_time_business_hours')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['support_category', 'loyalty_tier_id', 'account_type']);
        });

        Schema::create('business_hours', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('sla_definition_id')->nullable()->constrained('sla_definitions')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->json('days_of_week'); // [1,2,3,4,5]
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone', 100);
            $table->boolean('is_global')->default(false);
            $table->timestamps();
        });

        Schema::create('sla_instances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignUlid('sla_definition_id')->constrained('sla_definitions');
            $table->timestamp('assigned_at');
            $table->timestamp('first_response_deadline')->nullable();
            $table->timestamp('resolution_deadline')->nullable();
            $table->timestamp('first_response_met_at')->nullable();
            $table->timestamp('resolution_met_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->boolean('first_response_breached')->default(false);
            $table->boolean('resolution_breached')->default(false);
            $table->timestamps();

            $table->index(['ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_instances');
        Schema::dropIfExists('business_hours');
        Schema::dropIfExists('sla_definitions');
    }
};

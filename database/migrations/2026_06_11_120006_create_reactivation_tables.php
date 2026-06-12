<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reactivation_configs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('contact_type'); // lead | prospect | customer | partner | all
            $table->unsignedInteger('inactivity_days_threshold')->default(90);
            $table->foreignUlid('drip_sequence_id')->nullable()->constrained('drip_sequences')->nullOnDelete();
            $table->string('dormant_tag')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['contact_type']);
        });

        Schema::create('reactivation_contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('config_id')->constrained('reactivation_configs')->cascadeOnDelete();
            $table->foreignUlid('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignUlid('drip_enrolment_id')->nullable()->constrained('drip_enrolments')->nullOnDelete();
            $table->enum('status', ['enrolled', 're_engaged', 'completed', 'dormant'])->default('enrolled');
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamp('re_engaged_at')->nullable();
            $table->timestamps();

            $table->unique(['config_id', 'contact_id']);
            $table->index(['contact_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactivation_contacts');
        Schema::dropIfExists('reactivation_configs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drip_enrolments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('drip_sequence_id')->constrained('drip_sequences');
            $table->foreignUlid('contact_id')->constrained('contacts');
            $table->json('current_step_data')->nullable(); // Track current step position
            $table->enum('status', ['active', 'completed', 'exited', 'errored'])->default('active');
            $table->timestamp('enroled_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['drip_sequence_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_enrolments');
    }
};
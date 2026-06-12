<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drip_sequences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('trigger', ['contact_created', 'contact_stage_changed', 'deal_stage_changed', 'contact_field_changed', 'form_submission', 'manual_enrolment']);
            $table->json('trigger_conditions')->nullable(); // For stage/field triggers
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->foreignUlid('created_by')->constrained('users');
            $table->boolean('allow_re_enrolment')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_sequences');
    }
};
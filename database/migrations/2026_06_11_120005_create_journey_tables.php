<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guided_journeys', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('steps');
            $table->boolean('is_published')->default(false);
            $table->boolean('notify_agent_on_completion')->default(false);
            $table->foreignUlid('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('journey_completions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('journey_id')->constrained('guided_journeys')->cascadeOnDelete();
            $table->foreignUlid('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->json('inputs');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['journey_id', 'is_completed']);
            $table->index(['contact_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_completions');
        Schema::dropIfExists('guided_journeys');
    }
};

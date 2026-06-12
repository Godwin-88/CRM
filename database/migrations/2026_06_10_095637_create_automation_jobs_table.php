<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_jobs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('deal_automation_id')->constrained('deal_automations');
            $table->foreignUlid('deal_id')->constrained('deals');
            $table->foreignUlid('automation_action_id')->constrained('automation_actions');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_jobs');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_conversations', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('session_id', 255)->unique();
            $table->string('model_provider', 50)->default('anthropic');
            $table->string('model_name', 100)->default('claude-3-5-sonnet');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('tool_calls_count')->default(0);
            $table->unsignedInteger('write_significant_confirmed')->default(0);
            $table->unsignedInteger('write_significant_cancelled')->default(0);
            $table->unsignedInteger('feedback_positive')->default(0);
            $table->unsignedInteger('feedback_negative')->default(0);
            $table->text('feedback_comment')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();

            $table->index('user_id');
            $table->index('session_id');
            $table->index('started_at');
        });

        Schema::create('assistant_tool_calls', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->foreignUlid('conversation_id')->constrained('assistant_conversations')->cascadeOnDelete();
            $table->string('tool_name', 100);
            $table->json('input_json');
            $table->json('output_json');
            $table->string('tier', 50);
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamp('created_at');

            $table->index('conversation_id');
            $table->index('tool_name');
            $table->index('created_at');
        });

        Schema::create('assistant_low_confidence_routes', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('session_id', 255);
            $table->text('user_query');
            $table->string('resolved_intent', 100);
            $table->float('confidence_score');
            $table->boolean('flagged')->default(true);
            $table->timestamp('created_at');

            $table->index('session_id');
            $table->index('flagged');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_low_confidence_routes');
        Schema::dropIfExists('assistant_tool_calls');
        Schema::dropIfExists('assistant_conversations');
    }
};

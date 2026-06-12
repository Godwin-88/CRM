<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('segment_id')->nullable()->constrained('segments')->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['nps', 'csat']);
            $table->text('question_text');
            $table->text('follow_up_question')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'completed'])->default('draft');
            $table->string('channel')->default('email'); // email | sms
            $table->json('contact_ids')->nullable(); // explicit contact list override
            $table->foreignUlid('created_by')->constrained('users');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
        });

        Schema::create('survey_responses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('survey_id')->constrained('surveys')->cascadeOnDelete();
            $table->foreignUlid('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->integer('score');
            $table->text('open_text_answer')->nullable();
            $table->enum('channel', ['email', 'sms', 'portal']);
            $table->string('nps_category')->nullable(); // promoter | passive | detractor
            $table->timestamp('responded_at');
            $table->timestamps();

            $table->unique(['survey_id', 'contact_id']);
            $table->index(['contact_id', 'responded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('surveys');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_ab_tests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('campaign_id')->constrained('campaigns');
            $table->string('test_type'); // subject_line, content_variant, send_time
            $table->string('winner_criterion'); // open_rate, click_rate, conversion
            $table->integer('test_percentage'); // % of audience in test
            $table->integer('duration_hours'); // Test duration (min 1, max 72)
            $table->timestamp('started_at')->nullable();
            $table->timestamp('concluded_at')->nullable();
            $table->enum('status', ['pending', 'running', 'concluded', 'inconclusive'])->default('pending');
            $table->foreignUlid('variant_a_template_id')->nullable()->constrained('campaign_templates');
            $table->foreignUlid('variant_b_template_id')->nullable()->constrained('campaign_templates');
            $table->string('winning_variant')->nullable(); // A, B, or null
            $table->text('subject_line_a')->nullable();
            $table->text('subject_line_b')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_ab_tests');
    }
};
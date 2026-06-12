<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('subject')->nullable();
            $table->longText('html_content')->nullable();
            $table->longText('raw_html')->nullable(); // Original HTML before sanitization
            $table->enum('status', ['draft', 'in_review', 'approved', 'published', 'archived'])->default('draft');
            $table->foreignUlid('created_by')->constrained('users');
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->json('blocks')->nullable(); // For drag-and-drop editor structure
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_templates');
    }
};
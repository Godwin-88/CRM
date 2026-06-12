<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('campaign_id')->nullable()->constrained('campaigns');
            $table->enum('channel', ['linkedin', 'x', 'facebook']);
            $table->text('content');
            $table->string('media_url')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('impressions')->default(0);
            $table->enum('status', ['draft', 'scheduled', 'published', 'failed'])->default('draft');
            $table->text('error_message')->nullable();
            $table->string('external_id')->nullable(); // ID from social provider API
            $table->json('channel_specific_data')->nullable(); // Per-channel settings
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
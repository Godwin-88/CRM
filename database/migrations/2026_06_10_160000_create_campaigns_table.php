<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['email', 'sms', 'push', 'in_app', 'multi_channel', 'social']);
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'paused', 'cancelled', 'failed'])->default('draft');
            $table->foreignUlid('segment_id')->nullable()->constrained('segments');
            $table->foreignUlid('created_by')->constrained('users');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('throttle_emails_per_hour')->default(5000);
            $table->integer('throttle_sms_per_hour')->default(1000);
            $table->boolean('optimize_send_time')->default(false);
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('campaign_id')->constrained('campaigns');
            $table->integer('position');
            $table->enum('channel', ['email', 'sms', 'push', 'in_app']);
            $table->foreignUlid('email_template_id')->nullable()->constrained('campaign_templates');
            $table->string('sms_content')->nullable(); // For SMS step content
            $table->string('push_title')->nullable();
            $table->text('push_content')->nullable();
            $table->string('in_app_title')->nullable();
            $table->text('in_app_content')->nullable();
            $table->enum('delay_type', ['immediately', 'n_hours', 'n_days']);
            $table->integer('delay_value')->default(0); // N hours/days
            $table->enum('status', ['pending', 'sending', 'sent', 'skipped', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_steps');
    }
};
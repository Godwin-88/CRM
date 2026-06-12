<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('campaign_id')->constrained('campaigns');
            $table->foreignUlid('campaign_step_id')->constrained('campaign_steps');
            $table->foreignUlid('contact_id')->constrained('contacts');
            $table->enum('status', ['pending', 'sending', 'sent', 'delivered', 'failed', 'bounced', 'opened', 'clicked', 'unsubscribed', 'skipped']);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->string('error_message')->nullable();
            $table->string('tracking_token')->nullable();
            $table->string('redirect_url')->nullable(); // For click/open tracking
            $table->json('channel_eligibility')->nullable(); // Which channels were eligible for this contact
            $table->timestamps();

            $table->unique(['campaign_id', 'contact_id', 'campaign_step_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
    }
};
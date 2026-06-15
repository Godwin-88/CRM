<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('url');
            $table->json('events');
            $table->string('signing_secret');
            $table->foreignUlid('created_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_pause')->default(true);
            $table->integer('consecutive_failures')->default(0);
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'created_by']);
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('webhook_id')->constrained('webhooks')->onDelete('cascade');
            $table->string('event');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempt_number')->default(1);
            $table->integer('response_status_code')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['webhook_id', 'status']);
            $table->index(['webhook_id', 'attempt_number']);
        });

        Schema::create('inbound_webhook_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('provider');
            $table->string('event_id')->nullable();
            $table->string('signature_header')->nullable();
            $table->json('payload');
            $table->string('status')->default('received');
            $table->text('processing_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'event_id']);
            $table->index('created_at');
        });

        Schema::create('integration_oauth_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('redirect_uris');
            $table->json('grant_types');
            $table->string('client_id')->unique();
            $table->string('client_secret')->nullable();
            $table->boolean('is_personal')->default(false);
            $table->foreignUlid('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->json('scopes')->nullable();
            $table->boolean('is_suspended')->default(false);
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->timestamps();

            $table->index(['is_personal', 'user_id']);
        });

        Schema::table('integrations', function (Blueprint $table) {
            $table->string('api_key')->nullable();
            $table->string('category')->nullable();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('connection_status')->default('not_connected');
            $table->timestamp('last_active_at')->nullable();
            $table->string('rate_limit_key')->nullable();
            $table->json('webhook_events')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropColumn(['api_key', 'category', 'logo', 'description', 'connection_status', 'last_active_at', 'webhook_events', 'rate_limit_key']);
        });
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('inbound_webhook_logs');
        Schema::dropIfExists('integration_oauth_clients');
    }
};
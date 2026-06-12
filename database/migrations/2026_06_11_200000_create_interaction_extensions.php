<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('interaction_channels', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name'); // email, call, chat, sms, ivr, field_visit, kiosk, in_person
            $table->string('display_name');
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('integrations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('type'); // email_imap, email_webhook, twilio, africastalking, ctivendor, kiosk, ivr
            $table->string('provider'); // mailgun, postmark, twilio, africastalking, custom
            $table->json('config')->nullable(); // host, port, username, password, api_key, webhook_secret, etc.
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unmatched_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->enum('source_type', ['email', 'call', 'sms', 'ivr', 'kiosk']);
            $table->string('external_id')->nullable();
            $table->json('raw_payload');
            $table->foreignUlid('matched_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['unmatched', 'assigned', 'resolved'])->default('unmatched');
            $table->text('resolution_note')->nullable();
            $table->foreignUlid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'status']);
            $table->index(['matched_contact_id']);
        });

        Schema::create('interaction_attachments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('interaction_id')->constrained('interactions')->cascadeOnDelete();
            $table->string('filename');
            $table->string('mime_type');
            $table->integer('size_bytes');
            $table->string('storage_path');
            $table->string('disk')->default('s3');
            $table->timestamps();
        });

        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('interaction_id')->constrained('interactions')->cascadeOnDelete();
            $table->string('visitor_token');
            $table->string('visitor_email')->nullable();
            $table->string('visitor_name')->nullable();
            $table->foreignUlid('matched_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignUlid('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['waiting', 'active', 'closed', 'no_answer'])->default('waiting');
            $table->integer('wait_time_seconds')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'assigned_agent_id']);
            $table->index(['visitor_token']);
        });

        Schema::create('call_recordings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('interaction_id')->constrained('interactions')->cascadeOnDelete();
            $table->string('provider_call_sid')->nullable();
            $table->string('recording_url');
            $table->string('storage_path')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->timestamps();
        });

        Schema::table('interactions', function (Blueprint $table) {
            $table->foreignUlid('channel_id')->nullable()->constrained('interaction_channels')->nullOnDelete()->after('id');
            $table->string('external_message_id')->nullable()->after('subject'); // for threading
            $table->string('parent_interaction_id')->nullable()->after('external_message_id'); // for threading replies
            $table->json('metadata')->nullable()->after('outcome');
            $table->boolean('is_reviewed')->default(false)->after('agent_id');
            $table->boolean('is_locked')->default(false)->after('is_reviewed');
            $table->foreignUlid('locked_by')->nullable()->constrained('users')->nullOnDelete()->after('is_locked');
            $table->timestamp('locked_at')->nullable()->after('locked_by');
        });
    }

    public function down(): void
    {
        Schema::table('interactions', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropColumn('channel_id', 'external_message_id', 'parent_interaction_id', 'metadata', 'is_reviewed', 'is_locked', 'locked_by', 'locked_at');
        });
        Schema::dropIfExists('call_recordings');
        Schema::dropIfExists('chat_sessions');
        Schema::dropIfExists('interaction_attachments');
        Schema::dropIfExists('unmatched_items');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('interaction_channels');
    }
};

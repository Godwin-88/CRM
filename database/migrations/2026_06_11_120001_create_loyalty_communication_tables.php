<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loyalty_communication_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('program_id')->nullable()->constrained('loyalty_programs')->nullOnDelete();
            $table->enum('trigger_type', [
                'tier_upgrade',
                'tier_downgrade',
                'points_earned',
                'points_expiry_warning',
                'redemption_confirmation',
            ]);
            $table->enum('channel', ['email', 'sms', 'inapp']);
            $table->string('name');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('variables')->nullable(); // supported template variables
            $table->text('approval_note')->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected'])->default('draft');
            $table->foreignUlid('created_by')->constrained('users');
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['program_id', 'trigger_type', 'channel', 'status']);
        });

        Schema::create('loyalty_communication_preferences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->boolean('loyalty_opt_out')->default(false);
            $table->timestamp('loyalty_opt_out_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['contact_id']);
        });

        Schema::create('loyalty_communication_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('template_id')->nullable()->constrained('loyalty_communication_templates')->nullOnDelete();
            $table->foreignUlid('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignUlid('enrollment_id')->nullable()->constrained('loyalty_enrollments')->nullOnDelete();
            $table->enum('channel', ['email', 'sms', 'inapp']);
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed', 'bounced'])->default('queued');
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['contact_id', 'status', 'sent_at']);
            $table->index(['template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_communication_logs');
        Schema::dropIfExists('loyalty_communication_preferences');
        Schema::dropIfExists('loyalty_communication_templates');
    }
};

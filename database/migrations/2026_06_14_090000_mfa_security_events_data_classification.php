<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('mfa_enabled')->default(false);
            $table->text('mfa_secret_encrypted')->nullable();
            $table->text('mfa_recovery_codes_encrypted')->nullable();
            $table->timestamp('mfa_lockout_until')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->integer('mfa_failed_attempts')->default(0);
            $table->timestamp('password_expires_at')->nullable();
        });

        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->ulid('user_id')->nullable();
            $table->string('email')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('outcome');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('data_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->string('field_name');
            $table->enum('sensitivity', ['pii', 'financial', 'confidential']);
            $table->timestamps();

            $table->unique(['model_type', 'field_name'], 'data_classifications_unique_field');
            $table->index(['model_type', 'sensitivity']);
        });

        Schema::create('temporary_field_access', function (Blueprint $table) {
            $table->id();
            $table->ulid('user_id');
            $table->string('model_type');
            $table->ulid('model_id');
            $table->string('field_name');
            $table->timestamp('expires_at');
            $table->string('justification');
            $table->timestamps();

            $table->index(['user_id', 'model_id', 'field_name']);
            $table->index('expires_at');
        });

        Schema::create('dsr_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('type');
            $table->ulid('contact_id');
            $table->string('requested_by');
            $table->string('status')->default('pending');
            $table->ulid('handled_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('blocking_reason')->nullable();
            $table->text('justification')->nullable();
            $table->timestamps();
        });

        Schema::create('rate_limits', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->integer('max_requests');
            $table->integer('window_seconds');
            $table->timestamps();

            $table->unique('key');
        });

        Schema::create('password_history', function (Blueprint $table) {
            $table->id();
            $table->ulid('user_id');
            $table->string('password_hash');
            $table->timestamp('created_at');

            $table->index('user_id');
            $table->index('created_at');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('marketing_consent')->default(false);
            $table->boolean('data_processing_consent')->default(false);
            $table->timestamp('consent_timestamp')->nullable();
            $table->string('national_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mfa_enabled',
                'mfa_secret_encrypted',
                'mfa_recovery_codes_encrypted',
                'mfa_lockout_until',
                'locked_until',
                'mfa_failed_attempts',
                'password_expires_at',
            ]);
        });

        Schema::dropIfExists('security_events');
        Schema::dropIfExists('data_classifications');
        Schema::dropIfExists('temporary_field_access');
        Schema::dropIfExists('dsr_requests');
        Schema::dropIfExists('rate_limits');
        Schema::dropIfExists('password_history');

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['marketing_consent', 'data_processing_consent', 'consent_timestamp', 'national_id']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drip_sequence_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('drip_sequence_id')->constrained('drip_sequences');
            $table->integer('position');
            $table->enum('action_type', ['send_email', 'send_sms', 'send_in_app', 'create_activity', 'update_contact_field', 'add_to_segment', 'remove_from_segment', 'notify_agent']);
            $table->foreignUlid('email_template_id')->nullable()->constrained('campaign_templates');
            $table->string('sms_content')->nullable();
            $table->string('in_app_title')->nullable();
            $table->text('in_app_content')->nullable();
            $table->string('activity_type')->nullable();
            $table->string('field_key')->nullable();
            $table->string('field_value')->nullable();
            $table->foreignUlid('segment_id')->nullable()->constrained('segments');
            $table->foreignUlid('agent_id')->nullable()->constrained('users');
            $table->enum('delay_type', ['immediate', 'n_hours', 'n_days']);
            $table->integer('delay_value')->default(0);
            $table->json('exit_conditions')->nullable(); // Conditions to exit sequence
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_sequence_steps');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_actions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('deal_automation_id')->constrained('deal_automations')->cascadeOnDelete();
            $table->enum('action_type', ['create_activity', 'send_email', 'send_webhook'])->default('create_activity');
            $table->enum('delay_type', ['immediate', 'one_hour', 'one_day', 'n_business_days'])->default('immediate');
            $table->integer('delay_days')->nullable();
            $table->foreignUlid('assigned_to')->nullable()->constrained('users');
            $table->string('email_to')->nullable();
            $table->string('webhook_url')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_actions');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id(); // auto-increment integer (Spatie v5 expects this)
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable(); // string for ULID support
            $table->index(['subject_type', 'subject_id']);
            $table->string('causer_type')->nullable();
            $table->string('causer_id')->nullable(); // string for ULID support
            $table->index(['causer_type', 'causer_id']);
            $table->json('properties')->nullable();
            $table->json('attribute_changes')->nullable();
            $table->string('event')->nullable();
            $table->string('batch_uuid')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    public function down(): void {
        Schema::dropIfExists('activity_log');
    }
};
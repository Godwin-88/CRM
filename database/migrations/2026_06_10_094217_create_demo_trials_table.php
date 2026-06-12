<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_trials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->enum('type', ['demo', 'trial']);
            $table->date('scheduled_date');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('scope_notes')->nullable();
            $table->foreignUlid('assigned_to')->constrained('users');
            $table->enum('status', ['scheduled', 'completed', 'no_show', 'converted'])->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_trials');
    }
};
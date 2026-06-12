<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('subject');
            $table->string('type');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignUlid('contact_id')->nullable()->constrained('contacts');
            $table->foreignUlid('deal_id')->nullable()->constrained('deals');
            $table->foreignUlid('account_id')->nullable()->constrained('accounts');
            $table->foreignUlid('assigned_to')->constrained('users');
            $table->string('priority')->default('medium');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('activities');
    }
};

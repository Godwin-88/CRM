<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('interactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contact_id')->constrained('contacts');
            $table->foreignUlid('account_id')->constrained('accounts');
            $table->foreignUlid('deal_id')->nullable()->constrained('deals');
            $table->string('type');
            $table->string('direction');
            $table->string('subject');
            $table->text('body')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('outcome')->nullable();
            $table->foreignUlid('agent_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('interactions');
    }
};

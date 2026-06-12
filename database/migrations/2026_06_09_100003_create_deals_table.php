<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('deals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->foreignUlid('account_id')->constrained('accounts');
            $table->foreignUlid('contact_id')->constrained('contacts');
            $table->string('stage');
            $table->decimal('value', 15, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->integer('probability')->default(0);
            $table->date('expected_close_date')->nullable();
            $table->foreignUlid('pipeline_id')->constrained('pipelines');
            $table->foreignUlid('owner_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('deals');
    }
};

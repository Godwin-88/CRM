<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('account_id')->constrained('accounts');
            $table->string('name');
            $table->string('type'); // text, number, date, select
            $table->json('options')->nullable();
            $table->timestamps();
        });

        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contact_id')->constrained('contacts');
            $table->foreignUlid('custom_field_id')->constrained('custom_fields');
            $table->text('value');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');
    }
};

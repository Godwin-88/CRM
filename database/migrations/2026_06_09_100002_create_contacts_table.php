<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('account_id')->nullable()->constrained('accounts');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('type'); // lead | prospect | customer | partner
            // TODO: Segments (pivot table needed later, but keeping placeholders for now)
            $table->string('status')->default('active');
            $table->string('source')->nullable();
            $table->foreignUlid('owner_id')->nullable()->constrained('users');
            $table->decimal('clv_score', 15, 2)->default(0);
            $table->string('loyalty_tier')->default('bronze');
            $table->string('preferred_channel')->default('email');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('contacts');
    }
};

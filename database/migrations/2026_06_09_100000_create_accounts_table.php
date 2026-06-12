<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::dropIfExists('accounts');
        Schema::create('accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('type')->nullable(); // prospect, customer, partner
            $table->string('industry')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->decimal('annual_revenue', 15, 2)->nullable();
            $table->integer('employee_count')->nullable();
            $table->foreignUlid('account_manager_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignUlid('parent_account_id')->nullable()->constrained('accounts');
        });
    }

    public function down(): void {
        Schema::dropIfExists('accounts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('type');
            $table->string('identifier')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('available');
            $table->unsignedInteger('useful_life_years')->nullable();
            $table->decimal('total_quantity', 15, 2)->nullable();
            $table->decimal('available_quantity', 15, 2)->nullable();
            $table->decimal('minimum_threshold', 15, 2)->nullable();
            $table->foreignUlid('assigned_to')->nullable()->constrained('users');
            $table->foreignUlid('assigned_to_account')->nullable()->constrained('accounts');
            $table->date('assignment_start_date')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
        });

        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('assigned_to')->nullable()->constrained('users');
            $table->foreignUlid('assigned_to_account')->nullable()->constrained('accounts');
            $table->date('assignment_start_date');
            $table->date('expected_return_date')->nullable();
            $table->date('returned_at')->nullable();
            $table->text('condition_note')->nullable();
            $table->boolean('requires_maintenance')->default(false);
            $table->timestamps();
        });

        Schema::create('asset_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_stock_trackable')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_types');
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
    }
};
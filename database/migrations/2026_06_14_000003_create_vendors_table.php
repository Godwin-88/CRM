<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('category');
            $table->string('primary_contact_name');
            $table->string('primary_contact_email');
            $table->string('primary_contact_phone');
            $table->string('registration_number')->nullable();
            $table->string('tax_identification_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch_code')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('physical_address')->nullable();
            $table->string('website')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('category');
        });

        Schema::create('vendor_ratings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('rated_by')->constrained('users');
            $table->foreignUlid('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quality');
            $table->integer('delivery_timeliness');
            $table->integer('communication');
            $table->integer('value_for_money');
            $table->text('notes')->nullable();
            $table->timestamp('rated_at');
            $table->timestamps();

            $table->index('rated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_ratings');
        Schema::dropIfExists('vendors');
    }
};
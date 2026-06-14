<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }
};
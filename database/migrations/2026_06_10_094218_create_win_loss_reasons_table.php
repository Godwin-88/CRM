<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('win_loss_reasons', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->enum('type', ['won', 'lost']);
            $table->string('label');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('win_loss_reasons');
    }
};
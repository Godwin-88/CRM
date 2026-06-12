<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('pipeline_id')->constrained('pipelines')->cascadeOnDelete();
            $table->string('name');
            $table->integer('probability')->default(0);
            $table->text('description')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_kpi_fields', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('contract_type')->index(); // msa|nda|sla|renewal|upsell|custom
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('type'); // numeric|percentage|date
            $table->boolean('is_required')->default(false);
            $table->json('settings')->nullable(); // min, max, unit, format, threshold
            $table->json('options')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['contract_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_kpi_fields');
    }
};

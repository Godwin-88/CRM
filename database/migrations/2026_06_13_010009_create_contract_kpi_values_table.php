<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_kpi_values', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignUlid('contract_kpi_field_id')->constrained('contract_kpi_fields')->cascadeOnDelete();
            $table->json('value');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignUlid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['contract_id', 'contract_kpi_field_id', 'period_start', 'period_end']);
            $table->index(['contract_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_kpi_values');
    }
};

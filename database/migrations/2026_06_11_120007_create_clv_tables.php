<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clv_calculations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->decimal('historical_clv', 15, 2)->default(0);
            $table->decimal('predicted_ltv', 15, 2)->nullable();
            $table->decimal('avg_deal_value', 15, 2)->nullable();
            $table->decimal('estimated_frequency', 10, 2)->nullable();
            $table->decimal('estimated_lifespan_years', 10, 2)->nullable();
            $table->integer('churn_risk_score')->nullable(); // 0-100
            $table->string('churn_risk_band')->nullable(); // low | medium | high
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['contact_id']);
            $table->index(['churn_risk_band']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clv_calculations');
    }
};

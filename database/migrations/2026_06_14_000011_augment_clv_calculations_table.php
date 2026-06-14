<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('clv_calculations', function (Blueprint $table) {
            $table->decimal('clv_score', 15, 2)->default(0)->after('contact_id');
            $table->decimal('ltv', 15, 2)->default(0)->after('clv_score');
            $table->decimal('total_revenue', 15, 2)->default(0)->after('predicted_ltv');
            $table->integer('engagement_score')->default(0)->after('total_revenue');
            $table->decimal('loyalty_boost', 15, 2)->default(0)->after('engagement_score');
            $table->decimal('satisfaction_boost', 10, 2)->default(0)->after('loyalty_boost');
            $table->decimal('years_active', 10, 2)->default(0)->after('satisfaction_boost');
        });
    }

    public function down(): void {
        Schema::table('clv_calculations', function (Blueprint $table) {
            $table->dropColumn([
                'clv_score',
                'ltv',
                'total_revenue',
                'engagement_score',
                'loyalty_boost',
                'satisfaction_boost',
                'years_active'
            ]);
        });
    }
};

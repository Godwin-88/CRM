<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('contacts', function (Blueprint $table) {
            $table->decimal('ltv', 15, 2)->default(0)->after('clv_score');
            $table->integer('churn_risk_score')->default(0)->after('ltv');
            $table->timestamp('last_activity_at')->nullable()->after('churn_risk_score');
        });
    }

    public function down(): void {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['ltv', 'churn_risk_score', 'last_activity_at']);
        });
    }
};

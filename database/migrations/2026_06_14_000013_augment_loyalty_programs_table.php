<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->string('currency_symbol', 10)->default('pts')->after('currency_label');
            $table->decimal('earn_rate', 10, 2)->default(1.00)->after('currency_symbol'); // points per $1
            $table->unsignedInteger('min_redemption_threshold')->default(100)->after('earn_rate');
            $table->string('program_type')->default('points_based')->after('name'); // points_based, cashback, tiered
        });
    }

    public function down(): void {
        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->dropColumn(['currency_symbol', 'earn_rate', 'min_redemption_threshold', 'program_type']);
        });
    }
};

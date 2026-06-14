<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('drip_sequence_steps', function (Blueprint $table) {
            $table->jsonb('config')->nullable()->after('action_type'); // delays, email_template_id, branch_conditions
            $table->integer('delay_days')->default(0)->after('config');
            $table->integer('sort_order')->default(0)->after('delay_days');
        });
    }

    public function down(): void {
        Schema::table('drip_sequence_steps', function (Blueprint $table) {
            $table->dropColumn(['config', 'delay_days', 'sort_order']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('support_opt_out')->default(false)->after('preferred_channel');
        });
    }

    public function down(): void {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('support_opt_out');
        });
    }
};
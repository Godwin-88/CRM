<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->integer('duration_minutes')->nullable()->after('priority');
            $table->string('outcome')->nullable()->after('priority');
            $table->text('notes')->nullable()->after('priority');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['duration_minutes', 'outcome', 'notes']);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sla_definitions', function (Blueprint $table) {
            $table->string('priority')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('sla_definitions', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};

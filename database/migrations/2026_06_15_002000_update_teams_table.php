<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
            $table->boolean('is_archived')->default(false)->after('description');
            $table->foreignUlid('team_lead_id')->nullable()->constrained('users')->after('is_archived');
        });

        Schema::table('team_members', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['team_lead_id']);
            $table->dropColumn(['description', 'is_archived', 'team_lead_id']);
        });
    }
};
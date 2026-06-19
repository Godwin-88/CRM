<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revenue_targets', function (Blueprint $table) {
            if (! Schema::hasColumn('revenue_targets', 'created_by')) {
                $table->foreignUlid('created_by')->nullable()->after('team_id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::create('deal_stage_history', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->string('previous_stage');
            $table->string('next_stage');
            $table->integer('days_in_stage')->nullable();
            $table->timestamp('moved_at');
            $table->foreignUlid('moved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['deal_id', 'moved_at']);
            $table->index(['previous_stage', 'next_stage', 'moved_at']);
        });

        Schema::create('audit_retention_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->integer('value')->default(84);
            $table->timestamps();
        });

        DB::table('audit_retention_settings')->insertOrIgnore([
            'key' => 'audit_retention_months',
            'value' => 84,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_retention_settings');
        Schema::dropIfExists('deal_stage_history');
        Schema::table('revenue_targets', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};

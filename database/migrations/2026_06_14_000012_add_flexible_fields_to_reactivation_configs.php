<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('reactivation_configs', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->text('description')->nullable()->after('name');
            $table->jsonb('criteria')->nullable()->after('description');
            $table->jsonb('actions')->nullable()->after('criteria');
            
            // Allow these to be nullable if we transition to the new JSON-based system
            $table->string('contact_type')->nullable()->change();
            $table->unsignedInteger('inactivity_days_threshold')->nullable()->change();
        });
    }

    public function down(): void {
        Schema::table('reactivation_configs', function (Blueprint $table) {
            $table->dropColumn(['name', 'description', 'criteria', 'actions']);
            $table->string('contact_type')->nullable(false)->change();
            $table->unsignedInteger('inactivity_days_threshold')->nullable(false)->change();
        });
    }
};

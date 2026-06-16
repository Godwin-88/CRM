<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table) {
            $table->enum('audience', ['agent', 'manager', 'admin', 'all'])->default('all')->after('status');
            $table->json('feature_refs')->nullable()->after('audience');
            $table->timestamp('last_verified_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table) {
            $table->dropColumn(['audience', 'feature_refs', 'last_verified_at']);
        });
    }
};
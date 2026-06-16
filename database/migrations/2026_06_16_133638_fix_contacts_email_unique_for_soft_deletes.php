<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, we use a partial unique index to ignore soft-deleted records
        // First drop the standard unique index
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        // Add the partial unique index
        DB::statement('CREATE UNIQUE INDEX contacts_email_unique ON contacts (email) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to standard unique index
        DB::statement('DROP INDEX IF EXISTS contacts_email_unique');
        
        Schema::table('contacts', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};

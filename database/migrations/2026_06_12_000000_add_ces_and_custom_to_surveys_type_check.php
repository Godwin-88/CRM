<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE surveys DROP CONSTRAINT IF EXISTS surveys_type_check");

        Schema::table('surveys', function (Blueprint $table) {
            $table->string('type')->default('nps')->change();
        });

        DB::statement("ALTER TABLE surveys ADD CONSTRAINT surveys_type_check_v2 CHECK (type::text = ANY ((ARRAY['nps'::character varying, 'csat'::character varying, 'ces'::character varying, 'custom'::character varying])::text[]))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE surveys DROP CONSTRAINT IF EXISTS surveys_type_check_v2");

        Schema::table('surveys', function (Blueprint $table) {
            $table->enum('type', ['nps', 'csat'])->default('nps')->change();
        });

        DB::statement("ALTER TABLE surveys ADD CONSTRAINT surveys_type_check CHECK (type::text = ANY ((ARRAY['nps'::character varying, 'csat'::character varying])::text[]))");
    }
};

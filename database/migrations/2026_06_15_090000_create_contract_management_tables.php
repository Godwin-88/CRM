<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users');
            $table->jsonb('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_clauses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('body');
            $table->string('category')->nullable();
            $table->string('type');
            $table->boolean('is_global')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users');
            $table->jsonb('variables')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_template_clause', function (Blueprint $table) {
            $table->foreignUlid('contract_template_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('contract_clause_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_optional')->default(false);
            $table->boolean('is_included_by_default')->default(true);
            $table->jsonb('template_variables')->nullable();
            $table->timestamps();
            $table->primary(['contract_template_id', 'contract_clause_id'], 'template_clause_primary');
        });

        Schema::create('contract_template_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contract_template_id')->constrained()->cascadeOnDelete();
            $table->integer('version_number');
            $table->jsonb('content');
            $table->jsonb('change_summary')->nullable();
            $table->foreignUlid('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_template_versions');
        Schema::dropIfExists('contract_template_clause');
        Schema::dropIfExists('contract_clauses');
        Schema::dropIfExists('contract_templates');
    }
};

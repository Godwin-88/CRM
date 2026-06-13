<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_clauses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('body');
            $table->string('category')->nullable()->index();
            $table->string('type')->nullable(); // standard|custom|regulatory
            $table->boolean('is_global')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('variables')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_template_clause', function (Blueprint $table) {
            $table->foreignUlid('contract_template_id')->constrained('contract_templates')->cascadeOnDelete();
            $table->foreignUlid('contract_clause_id')->constrained('contract_clauses')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_optional')->default(false);
            $table->boolean('is_included_by_default')->default(true);
            $table->json('template_variables')->nullable();
            $table->timestamps();

            $table->primary(['contract_template_id', 'contract_clause_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_template_clause');
        Schema::dropIfExists('contract_clauses');
    }
};

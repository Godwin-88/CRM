<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_tags', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->unique()->index();
            $table->string('slug')->unique()->index();
            $table->string('color')->default('#6366f1');
            $table->integer('usage_count')->default(0);
            $table->timestamps();
        });

        Schema::create('contract_contract_tag', function (Blueprint $table) {
            $table->foreignUlid('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignUlid('contract_tag_id')->constrained('contract_tags')->cascadeOnDelete();
            $table->primary(['contract_id', 'contract_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_contract_tag');
        Schema::dropIfExists('contract_tags');
    }
};

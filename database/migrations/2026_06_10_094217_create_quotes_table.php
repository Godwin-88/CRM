<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->foreignUlid('quote_template_id')->constrained('quote_templates');
            $table->foreignUlid('created_by')->constrained('users');
            $table->string('status')->default('draft');
            $table->decimal('total', 15, 2)->default(0);
            $table->string('pdf_path')->nullable();
            $table->string('shareable_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};

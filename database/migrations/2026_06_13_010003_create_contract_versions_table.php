<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('status')->default('draft'); // draft|sent|signed|active|expired|terminated
            $table->string('document_path')->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('variables')->nullable();
            $table->json('selected_clauses')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'version_number']);
            $table->index(['contract_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_versions');
    }
};

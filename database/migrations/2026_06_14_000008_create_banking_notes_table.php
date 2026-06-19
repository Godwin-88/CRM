<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banking_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('banking_relationship_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained();
            $table->text('content');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['banking_relationship_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banking_notes');
    }
};
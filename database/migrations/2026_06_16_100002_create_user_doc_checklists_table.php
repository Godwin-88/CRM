<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_doc_checklists', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users');
            $table->string('checklist_item_key');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'checklist_item_key']);
            $table->index(['user_id', 'completed_at']);
            $table->index(['checklist_item_key', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_doc_checklists');
    }
};
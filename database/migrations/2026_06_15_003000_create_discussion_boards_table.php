<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_boards', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('boardable_type');
            $table->ulid('boardable_id');
            $table->string('title')->default('Discussion');
            $table->timestamps();

            $table->index(['boardable_type', 'boardable_id']);
        });

        Schema::create('discussion_threads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('discussion_board_id')->constrained('discussion_boards')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users');
            $table->string('title');
            $table->text('body');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['is_pinned', 'created_at']);
        });

        Schema::create('discussion_replies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('thread_id')->constrained('discussion_threads')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users');
            $table->text('body');
            $table->ulid('parent_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_replies');
        Schema::dropIfExists('discussion_threads');
        Schema::dropIfExists('discussion_boards');
    }
};
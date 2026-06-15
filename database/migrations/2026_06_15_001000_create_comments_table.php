<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('commentable_type');
            $table->ulid('commentable_id');
            $table->foreignUlid('user_id')->constrained('users');
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->foreignUlid('deleted_by_id')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id']);
            $table->index('created_at');
        });

        Schema::create('comment_mentions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['comment_id', 'user_id']);
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_mentions');
        Schema::dropIfExists('comments');
    }
};
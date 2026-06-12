<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_comments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users');
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('deal_comment_mentions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('deal_comment_id')->constrained('deal_comments')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_comments');
        Schema::dropIfExists('deal_comment_mentions');
    }
};
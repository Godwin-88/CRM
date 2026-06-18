<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_internal_tokens', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 255);
            $table->json('abilities');
            $table->timestamp('expires_at');
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();

            $table->index('token_hash');
            $table->index('user_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_internal_tokens');
    }
};

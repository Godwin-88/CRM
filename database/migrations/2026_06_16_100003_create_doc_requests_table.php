<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('screen_identifier');
            $table->foreignUlid('user_id')->constrained('users');
            $table->text('comment')->nullable();
            $table->unsignedInteger('request_count')->default(1);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['screen_identifier', 'resolved_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_requests');
    }
};
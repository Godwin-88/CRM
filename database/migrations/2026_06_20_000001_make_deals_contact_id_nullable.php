<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->foreignUlid('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->foreignUlid('contact_id')->nullable(false)->constrained('contacts');
        });
    }
};

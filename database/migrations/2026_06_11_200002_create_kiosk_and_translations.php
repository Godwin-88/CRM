<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kiosk_integrations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('kiosk_id')->unique();
            $table->text('api_key');
            $table->text('old_api_key')->nullable();
            $table->timestamp('old_key_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('interface_language', 10)->default('en')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('interface_language');
        });
        Schema::dropIfExists('kiosk_integrations');
    }
};

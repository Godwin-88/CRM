<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banking_relationships', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('institution_name');
            $table->string('relationship_type');
            $table->string('relationship_manager_name');
            $table->string('relationship_manager_email');
            $table->string('relationship_manager_phone');
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->date('facility_expiry_date')->nullable();
            $table->string('interest_rate')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('relationship_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banking_relationships');
    }
};
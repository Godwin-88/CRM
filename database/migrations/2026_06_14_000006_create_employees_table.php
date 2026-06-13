<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_number')->unique();
            $table->string('department');
            $table->string('job_title');
            $table->string('employment_type');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('employment_status')->default('active');
            $table->foreignUlid('reporting_manager_id')->nullable()->references('id')->on('users');
            $table->timestamps();

            $table->index('department');
            $table->index('employment_status');
        });

        Schema::create('department_headcounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->unsignedInteger('target_headcount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_headcounts');
        Schema::dropIfExists('employees');
    }
};
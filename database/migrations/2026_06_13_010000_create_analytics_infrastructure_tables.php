<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->ulid('owner_id');
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->enum('visibility', ['private', 'shared'])->default('private');
            $table->string('entity_type');
            $table->json('filters')->nullable();
            $table->json('fields')->nullable();
            $table->string('sort_field')->nullable();
            $table->string('sort_direction')->default('asc');
            $table->string('group_by')->nullable();
            $table->string('chart_type')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'entity_type']);
        });

        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('report_id');
            $table->foreign('report_id')->references('id')->on('report_definitions')->onDelete('cascade');
            $table->enum('frequency', ['daily', 'weekly', 'monthly']);
            $table->integer('day_of_week')->nullable();
            $table->integer('day_of_month')->nullable();
            $table->json('recipients');
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('report_delivery_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('scheduled_report_id');
            $table->foreign('scheduled_report_id')->references('id')->on('scheduled_reports')->onDelete('cascade');
            $table->timestamp('sent_at');
            $table->json('recipients');
            $table->integer('row_count')->default(0);
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->decimal('budget', 12, 2)->nullable()->default(0);
            $table->decimal('spent', 12, 2)->nullable()->default(0);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('sku')->unique()->nullable();
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->integer('predicted_score')->nullable();
            $table->integer('manual_score')->nullable();
            $table->text('score_override_note')->nullable();
            $table->timestamp('score_last_calculated_at')->nullable();
            $table->ulid('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->string('billing_country')->nullable();
        });

        Schema::create('audit_anomalies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('event_type');
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->ulid('acknowledged_by')->nullable();
            $table->foreign('acknowledged_by')->references('id')->on('users')->onDelete('set null');
            $table->text('acknowledged_note')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'event_type', 'detected_at']);
        });

        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('widget_key');
            $table->integer('position')->default(0);
            $table->json('settings')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'widget_key']);
            $table->index(['user_id', 'position']);
        });

        Schema::create('revenue_targets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('team_id')->nullable();
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null');
            $table->string('period');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('target_revenue', 14, 2);
            $table->timestamps();

            $table->index(['team_id', 'period_start', 'period_end']);
        });

        Schema::create('lead_sources', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('acquisition_cost', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_sources');
        Schema::dropIfExists('revenue_targets');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('audit_anomalies');
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('report_delivery_logs');
        Schema::dropIfExists('report_definitions');
        Schema::dropIfExists('products');
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn(['predicted_score', 'manual_score', 'score_override_note', 'score_last_calculated_at', 'product_id']);
        });
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('billing_country');
        });
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['budget', 'spent']);
        });
    }
};
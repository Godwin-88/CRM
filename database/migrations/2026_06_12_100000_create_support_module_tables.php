<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Teams for support
        Schema::create('teams', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('teams');
            $table->foreignUlid('user_id')->constrained('users');
            $table->string('role')->default('agent'); // agent, manager, admin
            $table->timestamps();
        });

        // Ticket categories (hierarchical up to 2 levels)
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignUlid('parent_id')->nullable();
            $table->string('default_priority')->default('medium');
            $table->foreignUlid('default_team_id')->nullable()->constrained('teams');
            $table->foreignUlid('sla_policy_id')->nullable()->constrained('sla_definitions');
            $table->boolean('is_agent_only')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('ticket_categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('ticket_categories')->nullOnDelete();
        });

        // Custom forms for categories
        Schema::create('ticket_forms', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('ticket_category_id')->constrained('ticket_categories');
            $table->string('name');
            $table->json('fields');
            $table->timestamps();
        });

        // Form field values on tickets
        Schema::create('ticket_form_responses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('ticket_id')->constrained('tickets');
            $table->foreignUlid('ticket_form_id')->constrained('ticket_forms');
            $table->foreignUlid('ticket_category_id')->constrained('ticket_categories');
            $table->json('response_data');
            $table->timestamps();
        });

        // Knowledge base categories
        Schema::create('knowledge_base_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignUlid('parent_id')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::table('knowledge_base_categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('knowledge_base_categories')->nullOnDelete();
        });

        // Knowledge base articles
        Schema::create('knowledge_base_articles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('body');
            $table->foreignUlid('category_id')->constrained('knowledge_base_categories');
            $table->foreignUlid('author_id')->constrained('users');
            $table->enum('status', ['draft', 'in_review', 'approved', 'published', 'archived'])->default('draft');
            $table->integer('view_count')->default(0);
            $table->integer('helpful_votes')->default(0);
            $table->integer('not_helpful_votes')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Article versions
        Schema::create('knowledge_base_article_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('article_id')->constrained('knowledge_base_articles');
            $table->unsignedInteger('version_number');
            $table->string('title');
            $table->text('body');
            $table->foreignUlid('author_id')->constrained('users');
            $table->timestamp('created_at');
        });

        // Article-ticket linking
        Schema::create('article_ticket_links', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('article_id')->constrained('knowledge_base_articles');
            $table->foreignUlid('ticket_id')->constrained('tickets');
            $table->timestamps();
        });

        // Internal notes on tickets
        Schema::create('ticket_internal_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('ticket_id')->constrained('tickets');
            $table->foreignUlid('author_id')->constrained('users');
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->foreignUlid('deleted_by_id')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });

        // Internal note mentions
        Schema::create('internal_note_mentions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('internal_note_id')->constrained('ticket_internal_notes');
            $table->foreignUlid('user_id')->constrained('users');
            $table->timestamps();
        });

        // Canned responses
        Schema::create('canned_responses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->text('body');
            $table->string('category_tag')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Canned response favorites per agent
        Schema::create('canned_response_favorites', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('canned_response_id')->constrained('canned_responses');
            $table->foreignUlid('user_id')->constrained('users');
            $table->timestamps();
        });

        // Ticket relations (link tickets without merging)
        Schema::create('ticket_relations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('ticket_id')->constrained('tickets');
            $table->foreignUlid('related_ticket_id')->constrained('tickets');
            $table->timestamps();
        });

        // CSAT ratings
        Schema::create('ticket_ratings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('ticket_id')->constrained('tickets')->unique();
            $table->unsignedTinyInteger('score'); // 1-5
            $table->text('comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        // Support email addresses
        Schema::create('support_email_addresses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('email');
            $table->foreignUlid('default_category_id')->nullable()->constrained('ticket_categories');
            $table->string('default_priority')->default('medium');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('support_email_addresses');
        Schema::dropIfExists('ticket_ratings');
        Schema::dropIfExists('ticket_relations');
        Schema::dropIfExists('canned_response_favorites');
        Schema::dropIfExists('canned_responses');
        Schema::dropIfExists('internal_note_mentions');
        Schema::dropIfExists('ticket_internal_notes');
        Schema::dropIfExists('article_ticket_links');
        Schema::dropIfExists('knowledge_base_article_versions');
        Schema::dropIfExists('knowledge_base_articles');
        Schema::dropIfExists('knowledge_base_categories');
        Schema::dropIfExists('ticket_form_responses');
        Schema::dropIfExists('ticket_forms');
        Schema::dropIfExists('ticket_categories');
    }
};
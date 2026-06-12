<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('subject');
            $table->longText('body'); // rich text HTML
            $table->json('variables')->nullable(); // placeholders available
            $table->boolean('is_active')->default(true);
            $table->foreignUlid('created_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('interactions', function (Blueprint $table) {
            $table->foreignUlid('template_id')->nullable()->constrained('email_templates')->nullOnDelete()->after('agent_id');
            $table->text('message_id_header')->nullable()->after('metadata'); // for email threading
        });
    }

    public function down(): void
    {
        Schema::table('interactions', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn('template_id', 'message_id_header');
        });
        Schema::dropIfExists('email_templates');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Update custom_fields to be polymorphic
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->string('entity_type')->after('id'); // App\Models\Contact or App\Models\Account
            $table->string('entity_id')->nullable()->after('entity_type'); // optional scoping to specific context
        });

        // Update custom_field_values to be polymorphic too
        Schema::table('custom_field_values', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropColumn('contact_id');
            $table->dropForeign(['custom_field_id']);
            $table->dropColumn('custom_field_id');
        });
        Schema::table('custom_field_values', function (Blueprint $table) {
            $table->string('customizable_type')->after('id');
            $table->string('customizable_id')->after('customizable_type');
            $table->string('field_key')->after('customizable_id'); // references custom_fields.name
            $table->index(['customizable_type', 'customizable_id']);
        });
    }

    public function down(): void {
        Schema::table('custom_field_values', function (Blueprint $table) {
            $table->dropColumn(['customizable_type', 'customizable_id', 'field_key']);
            $table->foreignUlid('contact_id')->constrained('contacts');
            $table->foreignUlid('custom_field_id')->constrained('custom_fields');
        });
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->dropColumn(['entity_type', 'entity_id']);
            $table->foreignUlid('account_id')->constrained('accounts');
        });
    }
};
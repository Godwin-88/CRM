<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('segments', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('goal')->nullable()->after('type');
            $table->string('status')->default('draft')->after('goal');
            $table->foreignUlid('campaign_id')->nullable()->after('status')->constrained('campaigns')->nullOnDelete();
            $table->json('tags')->nullable()->after('campaign_id');
            $table->json('channel_eligibility')->nullable()->after('tags');
            $table->foreignUlid('created_by')->nullable()->after('channel_eligibility')->constrained('users')->nullOnDelete();
            $table->timestamp('last_evaluated_at')->nullable()->after('contact_count_cached_at');
        });
    }

    public function down(): void
    {
        Schema::table('segments', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'goal',
                'status',
                'campaign_id',
                'tags',
                'channel_eligibility',
                'created_by',
                'last_evaluated_at',
            ]);
        });
    }
};
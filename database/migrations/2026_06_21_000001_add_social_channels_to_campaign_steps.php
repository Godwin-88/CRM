<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaign_steps', function (Blueprint $table) {
            $table->text('whatsapp_content')->nullable()->after('in_app_content');
            $table->text('facebook_content')->nullable()->after('whatsapp_content');
            $table->text('instagram_content')->nullable()->after('facebook_content');
            $table->text('tiktok_content')->nullable()->after('instagram_content');
            $table->text('linkedin_content')->nullable()->after('tiktok_content');
            $table->string('social_image_url')->nullable()->after('linkedin_content');
        });

        \Illuminate\Support\Facades\DB::statement("ALTER TABLE campaign_steps DROP CONSTRAINT IF EXISTS campaign_steps_channel_check");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE campaign_steps ADD CONSTRAINT campaign_steps_channel_check CHECK (channel IN ('email','sms','push','in_app','whatsapp','facebook','instagram','tiktok','linkedin'))");
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE campaign_steps DROP CONSTRAINT IF EXISTS campaign_steps_channel_check");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE campaign_steps ADD CONSTRAINT campaign_steps_channel_check CHECK (channel IN ('email','sms','push','in_app'))");

        Schema::table('campaign_steps', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_content',
                'facebook_content',
                'instagram_content',
                'tiktok_content',
                'linkedin_content',
                'social_image_url',
            ]);
        });
    }
};

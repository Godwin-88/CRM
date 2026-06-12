<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->foreignUlid('win_loss_reason_id')->nullable()->constrained('win_loss_reasons');
            $table->text('win_loss_note')->nullable();
            $table->boolean('exclude_from_automations')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropForeign(['win_loss_reason_id']);
            $table->dropColumn(['win_loss_reason_id', 'win_loss_note', 'exclude_from_automations']);
        });
    }
};
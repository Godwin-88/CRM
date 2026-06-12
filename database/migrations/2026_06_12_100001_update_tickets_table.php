<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->renameColumn('assignee_id', 'assigned_to')->useCurrent()->change();
            $table->text('description')->nullable()->after('subject');
            $table->foreignUlid('account_id')->nullable()->after('contact_id')->constrained('accounts');
            $table->foreignUlid('category_id')->nullable()->after('priority')->constrained('ticket_categories');
            $table->timestamp('sla_breached_at')->nullable()->after('status');
            $table->timestamp('resolved_at')->nullable()->after('sla_breached_at');
            $table->timestamp('closed_at')->nullable()->after('resolved_at');
            $table->foreignUlid('merged_into_ticket_id')->nullable()->after('closed_at')->constrained('tickets');
            $table->timestamp('merged_at')->nullable()->after('merged_into_ticket_id');
            $table->foreignUlid('split_from_ticket_id')->nullable()->after('merged_at')->constrained('tickets');
            $table->text('escalation_reason')->nullable()->after('split_from_ticket_id');
            $table->boolean('is_agent_created')->default(false)->after('escalation_reason');
            
            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->renameColumn('assigned_to', 'assignee_id')->change();
            $table->dropColumn([
                'description',
                'account_id',
                'category_id',
                'sla_breached_at',
                'resolved_at',
                'closed_at',
                'merged_into_ticket_id',
                'merged_at',
                'split_from_ticket_id',
                'escalation_reason',
                'is_agent_created',
            ]);
        });
    }
};
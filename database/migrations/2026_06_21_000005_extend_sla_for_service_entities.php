<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sla_definitions', function (Blueprint $table) {
            $table->string('target_entity_type')->nullable()->after('support_category');
            $table->foreignUlid('service_catalog_item_id')->nullable()->after('target_entity_type')->constrained('service_catalog_items')->nullOnDelete();
            $table->unsignedInteger('acknowledgement_time_business_hours')->nullable()->after('first_response_time_business_hours');
            $table->unsignedInteger('review_time_business_hours')->nullable()->after('acknowledgement_time_business_hours');
            $table->unsignedInteger('next_action_time_business_hours')->nullable()->after('review_time_business_hours');
            $table->unsignedInteger('completion_time_business_hours')->nullable()->after('next_action_time_business_hours');
            $table->unsignedInteger('triage_time_business_hours')->nullable()->after('completion_time_business_hours');
            $table->unsignedInteger('investigation_update_time_business_hours')->nullable()->after('triage_time_business_hours');
            $table->unsignedInteger('resolution_proposal_time_business_hours')->nullable()->after('investigation_update_time_business_hours');
            $table->unsignedInteger('closure_signoff_time_business_hours')->nullable()->after('resolution_proposal_time_business_hours');
            $table->json('milestone_definitions')->nullable()->after('closure_signoff_time_business_hours');
        });

        Schema::table('sla_instances', function (Blueprint $table) {
            $table->string('target_type')->nullable()->after('ticket_id');
            $table->foreignUlid('target_id')->nullable()->after('target_type');
            $table->string('entity_type')->nullable()->after('target_id');
            $table->json('milestone_definitions')->nullable()->after('entity_type');
            $table->json('milestone_states')->nullable()->after('milestone_definitions');
            $table->timestamp('acknowledgement_deadline')->nullable()->after('first_response_deadline');
            $table->timestamp('review_deadline')->nullable()->after('acknowledgement_deadline');
            $table->timestamp('next_action_deadline')->nullable()->after('review_deadline');
            $table->timestamp('completion_deadline')->nullable()->after('next_action_deadline');
            $table->timestamp('triage_deadline')->nullable()->after('completion_deadline');
            $table->timestamp('investigation_update_deadline')->nullable()->after('triage_deadline');
            $table->timestamp('resolution_proposal_deadline')->nullable()->after('investigation_update_deadline');
            $table->timestamp('closure_signoff_deadline')->nullable()->after('resolution_proposal_deadline');
            $table->timestamp('acknowledgement_met_at')->nullable()->after('first_response_met_at');
            $table->timestamp('review_met_at')->nullable()->after('acknowledgement_met_at');
            $table->timestamp('next_action_met_at')->nullable()->after('review_met_at');
            $table->timestamp('completion_met_at')->nullable()->after('next_action_met_at');
            $table->timestamp('triage_met_at')->nullable()->after('completion_met_at');
            $table->timestamp('investigation_update_met_at')->nullable()->after('triage_met_at');
            $table->timestamp('resolution_proposal_met_at')->nullable()->after('investigation_update_met_at');
            $table->timestamp('closure_signoff_met_at')->nullable()->after('resolution_proposal_met_at');
            $table->boolean('acknowledgement_breached')->default(false)->after('first_response_breached');
            $table->boolean('review_breached')->default(false)->after('acknowledgement_breached');
            $table->boolean('next_action_breached')->default(false)->after('review_breached');
            $table->boolean('completion_breached')->default(false)->after('next_action_breached');
            $table->boolean('triage_breached')->default(false)->after('completion_breached');
            $table->boolean('investigation_update_breached')->default(false)->after('triage_breached');
            $table->boolean('resolution_proposal_breached')->default(false)->after('investigation_update_breached');
            $table->boolean('closure_signoff_breached')->default(false)->after('resolution_proposal_breached');
            $table->string('pause_reason')->nullable()->after('resumed_at');
            $table->string('resume_reason')->nullable()->after('pause_reason');
            $table->index(['target_type', 'target_id']);
        });

        DB::statement("UPDATE sla_instances SET target_type = 'ticket', target_id = ticket_id, entity_type = 'ticket' WHERE target_type IS NULL AND ticket_id IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('sla_instances', function (Blueprint $table) {
            $table->dropIndex(['target_type', 'target_id']);
            $table->dropColumn([
                'target_type',
                'target_id',
                'entity_type',
                'milestone_definitions',
                'milestone_states',
                'acknowledgement_deadline',
                'review_deadline',
                'next_action_deadline',
                'completion_deadline',
                'triage_deadline',
                'investigation_update_deadline',
                'resolution_proposal_deadline',
                'closure_signoff_deadline',
                'acknowledgement_met_at',
                'review_met_at',
                'next_action_met_at',
                'completion_met_at',
                'triage_met_at',
                'investigation_update_met_at',
                'resolution_proposal_met_at',
                'closure_signoff_met_at',
                'acknowledgement_breached',
                'review_breached',
                'next_action_breached',
                'completion_breached',
                'triage_breached',
                'investigation_update_breached',
                'resolution_proposal_breached',
                'closure_signoff_breached',
                'pause_reason',
                'resume_reason',
            ]);
        });

        Schema::table('sla_definitions', function (Blueprint $table) {
            $table->dropForeign(['service_catalog_item_id']);
            $table->dropColumn([
                'target_entity_type',
                'service_catalog_item_id',
                'acknowledgement_time_business_hours',
                'review_time_business_hours',
                'next_action_time_business_hours',
                'completion_time_business_hours',
                'triage_time_business_hours',
                'investigation_update_time_business_hours',
                'resolution_proposal_time_business_hours',
                'closure_signoff_time_business_hours',
                'milestone_definitions',
            ]);
        });
    }
};

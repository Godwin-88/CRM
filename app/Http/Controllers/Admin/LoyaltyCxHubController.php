<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuidedJourney;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTier;
use App\Models\PointsLedger;
use App\Models\ReactivationConfig;
use App\Models\SlaDefinition;
use App\Models\SlaInstance;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\EmailTemplate;
use Inertia\Inertia;
use Inertia\Response;

class LoyaltyCxHubController extends Controller
{
    public function programs(): Response
    {
        $programs = LoyaltyProgram::with(['tiers', 'rules'])->orderByDesc('created_at')->get();
        $templates = EmailTemplate::orderByDesc('created_at')->get();

        return Inertia::render('Admin/LoyaltyPrograms', [
            'programs' => $programs,
            'templates' => $templates,
        ]);
    }

    public function journeys(): Response
    {
        $templates = \App\Models\OnboardingTemplate::orderByDesc('created_at')->get(['id', 'name', 'description', 'is_active']);
        $records = \App\Models\OnboardingRecord::with('contact')->orderByDesc('created_at')->limit(20)->get();
        $journeys = GuidedJourney::withCount(['completions', 'completions as in_progress_count' => function ($q) {
            $q->where('journey_completions.status', 'completed');
        }])->get()->map(function ($j) {
            return [
                'id' => $j->id,
                'name' => $j->name,
                'journey_type' => $j->journey_type ?? 'guided',
                'is_published' => $j->is_published,
                'completions' => $j->completions_count,
                'starts' => $j->completions_count + $j->in_progress_count,
            ];
        });
        $reactivationConfigs = ReactivationConfig::orderByDesc('created_at')->get();

        return Inertia::render('Admin/CustomerJourneys', [
            'onboardingTemplates' => $templates,
            'onboardingRecords' => $records,
            'journeys' => $journeys,
            'reactivationConfigs' => $reactivationConfigs,
        ]);
    }

    public function insights(): Response
    {
        $surveys = Survey::orderByDesc('created_at')->get();
        $surveyMeta = Survey::select('id', 'name', 'type')->orderByDesc('created_at')->get();

        $responses = SurveyResponse::with('survey')->orderByDesc('responded_at')->limit(500)->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'survey_id' => $r->survey_id,
                'survey_name' => $r->survey?->name ?? 'Unknown',
                'survey_type' => $r->survey?->type ?? 'unknown',
                'contact_name' => $r->contact_name ?? 'Unknown',
                'contact_email' => $r->contact_email ?? '',
                'score' => $r->score,
                'nps_category' => $r->nps_category,
                'open_text_answer' => $r->open_text_answer,
                'channel' => $r->channel,
                'responded_at' => $r->responded_at?->toIso8601String() ?? now()->toIso8601String(),
            ];
        });

        $stats = \App\Models\ClvCalculation::query()
            ->selectRaw('AVG(clv_score) as avg_clv')
            ->first();
        $churnCount = \App\Models\ClvCalculation::where('churn_risk_band', 'high')->count();

        $clvStats = [
            'avg_clv' => $stats->avg_clv ?? 0,
            'total_points_issued' => PointsLedger::where('type', 'credit')->sum('points_amount'),
            'total_points_redeemed' => PointsLedger::where('type', 'debit')->sum('points_amount'),
            'redemption_rate' => 0,
            'total_enrollments' => \App\Models\LoyaltyEnrollment::count(),
            'active_enrollments' => \App\Models\LoyaltyEnrollment::where('is_active', true)->count(),
            'churn_risk_count' => $churnCount,
        ];
        $issued = $clvStats['total_points_issued'];
        $redeemed = $clvStats['total_points_redeemed'];
        $clvStats['redemption_rate'] = $issued > 0 ? round(($redeemed / $issued) * 100, 1) : 0;

        $topContacts = \App\Models\ClvCalculation::orderByDesc('clv_score')->limit(20)->get()->map(function ($c) {
            return [
                'id' => $c->contact_id,
                'first_name' => $c->contact?->first_name ?? 'Unknown',
                'last_name' => $c->contact?->last_name ?? '',
                'email' => $c->contact?->email ?? '',
                'clv_score' => $c->clv_score,
                'ltv' => $c->predicted_ltv ?? $c->clv_score,
            ];
        });

        return Inertia::render('Admin/CxInsights', [
            'surveys' => $surveys,
            'surveyMeta' => $surveyMeta,
            'surveyResponses' => $responses,
            'clvStats' => $clvStats,
            'topContacts' => $topContacts,
        ]);
    }

    public function service(): Response
    {
        $slaDefinitions = SlaDefinition::with('businessHours')->orderByDesc('created_at')->get();
        $instances = SlaInstance::with(['ticket', 'slaDefinition'])->orderByDesc('created_at')->limit(100)->get()->map(function ($i) {
            return [
                'id' => $i->id,
                'ticket_id' => $i->ticket_id,
                'ticket' => $i->ticket ? ['subject' => $i->ticket->subject, 'priority' => $i->ticket->priority] : null,
                'sla_definition_id' => $i->sla_definition_id,
                'sla_definition' => $i->slaDefinition ? ['name' => $i->slaDefinition->name] : null,
                'first_response_deadline' => $i->first_response_deadline?->toDateTimeString() ?? '',
                'resolution_deadline' => $i->resolution_deadline?->toDateTimeString() ?? '',
                'first_response_breached' => $i->first_response_breached,
                'resolution_breached' => $i->resolution_breached,
            ];
        });

        return Inertia::render('Admin/ServiceDelivery', [
            'slaDefinitions' => $slaDefinitions,
            'businessHours' => \App\Models\BusinessHours::all(),
            'instances' => $instances,
        ]);
    }
}

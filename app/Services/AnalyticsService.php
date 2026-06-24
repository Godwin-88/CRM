<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\ClvCalculation;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Interaction;
use App\Models\SecurityEvent;
use App\Models\Segment;
use App\Models\SurveyResponse;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function dashboardMetrics(string $role, array $filters = []): array
    {
        $filters = $this->mergeRoleFilters($role, $filters);
        $cacheKey = 'dashboard_metrics:'.md5(json_encode([$role, $filters]));

        return Cache::remember($cacheKey, 900, function () use ($role, $filters) {
            return $this->buildDashboardMetrics($role, $filters);
        });
    }

    public function getDashboardMetrics(string $role, ?int $userId = null, ?int $teamId = null): array
    {
        $filters = $this->buildScopedFilters($role, $userId, $teamId);

        return $this->buildDashboardMetrics($role, $filters);
    }

    protected function buildDashboardMetrics(string $role, array $filters): array
    {
        $metrics = [
            'period' => 'current_month',
            'generated_at' => now()->toIso8601String(),
            'scope' => $role,
        ];

        $metrics['pipeline'] = $this->getPipelineMetrics($filters, $role);
        $metrics['activity'] = $this->getActivityMetrics($filters);
        $metrics['tickets'] = $this->getTicketMetrics($filters);
        $metrics['revenue'] = $this->getRevenueMetrics($filters);
        $metrics['system_health'] = $this->getSystemHealthMetrics();

        if ($role === 'manager') {
            $metrics['activity']['completion_rate'] = $this->getActivityCompletionRate($filters);
            $metrics['tickets']['sla_breach_count'] = $this->getTicketSlaBreachCount($filters);
            $metrics['agent_performance'] = $this->getAgentPerformance($filters['team_id'] ?? null);
        }

        if ($role === 'admin' || $role === 'manager') {
            $metrics['pipeline']['top_deals'] = $this->getTopDeals($filters, 5);
        }

        if ($role === 'admin') {
            $metrics['admin'] = $this->getAdminMetrics($filters);
        }

        return $metrics;
    }

    protected function mergeRoleFilters(string $role, array $filters): array
    {
        $scoped = [];

        if ($role === 'agent') {
            $scoped['owner_id'] = $filters['owner_id'] ?? null;
        } elseif ($role === 'manager') {
            $scoped['team_id'] = $filters['team_id'] ?? null;
        }

        foreach (['date_from', 'date_to', 'contact_type', 'region', 'pipeline_stage'] as $filter) {
            if (isset($filters[$filter])) {
                $scoped[$filter] = $filters[$filter];
            }
        }

        return array_filter($scoped, fn ($value) => $value !== null && $value !== '');
    }

    public function refreshDashboardMetrics(User $user): array
    {
        $role = $this->roleFor($user);
        $filters = $role === 'manager'
            ? ['team_id' => $user->primaryTeam?->team_id]
            : ['owner_id' => $user->id];

        Cache::forget('dashboard_metrics:'.md5(json_encode([$role, $filters])));

        return $this->dashboardMetrics($role, $filters);
    }

    protected function buildScopedFilters(string $role, ?int $userId, ?int $teamId): array
    {
        $filters = [];

        if ($role === 'agent' && $userId) {
            $filters['owner_id'] = $userId;
        } elseif ($role === 'manager' && $teamId) {
            $filters['team_id'] = $teamId;
        }

        return $filters;
    }

    protected function roleFor($user): string
    {
        if ($user->hasRole('admin')) {
            return 'admin';
        }

        if ($user->primaryTeam) {
            return 'manager';
        }

        return 'agent';
    }

    protected function getPipelineMetrics(array $filters, string $role): array
    {
        $query = Deal::query()->whereNotIn('stage', ['closed_won', 'closed_lost']);
        $this->applyScope($query, $filters);

        $openDeals = $query->with(['owner', 'account'])->get();
        $totalValue = $openDeals->sum('value');
        $weightedValue = $openDeals->sum(fn (Deal $deal) => $deal->getWeightedValue());

        $byStage = $openDeals->groupBy('stage')->map(fn ($deals, $stage) => [
            'stage' => $stage,
            'count' => $deals->count(),
            'value' => $deals->sum('value'),
            'weighted_value' => $deals->sum(fn (Deal $deal) => $deal->getWeightedValue()),
        ])->values();

        $recentInteractions = Interaction::query()
            ->with(['contact', 'account', 'deal', 'agent'])
            ->when(isset($filters['team_id']), fn ($q) => $q->whereHas('agent', fn ($qq) => $qq->whereHas('primaryTeam', fn ($team) => $team->where('team_id', $filters['team_id']))))
            ->when(isset($filters['owner_id']), fn ($q) => $q->where('agent_id', $filters['owner_id']))
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Interaction $interaction) => [
                'id' => $interaction->id,
                'type' => $interaction->type,
                'direction' => $interaction->direction,
                'subject' => $interaction->subject,
                'contact_name' => $interaction->contact ? trim($interaction->contact->first_name.' '.$interaction->contact->last_name) : null,
                'account_name' => $interaction->account?->name ?? null,
                'deal_title' => $interaction->deal?->title ?? null,
                'agent_name' => $interaction->agent?->name ?? null,
                'created_at' => $interaction->created_at?->toIso8601String(),
            ]);

        return [
            'open_deal_count' => $openDeals->count(),
            'open_deal_value' => $totalValue,
            'weighted_pipeline_value' => $weightedValue,
            'by_stage' => $byStage,
            'recent_interactions' => $recentInteractions,
            'top_deals' => in_array($role, ['admin', 'manager']) ? $this->getTopDeals($filters, 5) : [],
        ];
    }

    protected function getActivityMetrics(array $filters): array
    {
        $query = Activity::query();
        $this->applyScope($query, $filters);

        $today = Carbon::today();
        $baseDueToday = $query->whereDate('due_at', $today)->whereNull('completed_at')->count();
        $baseOverdue = $query->where('due_at', '<', $today)->whereNull('completed_at')->count();
        $completionRate = $this->getActivityCompletionRate($filters);

        return [
            'due_today' => $baseDueToday,
            'overdue' => $baseOverdue,
            'completion_rate' => $completionRate,
        ];
    }

    protected function getTicketMetrics(array $filters): array
    {
        $query = Ticket::query();
        $this->applyScope($query, $filters);

        $openTickets = $query->whereNotIn('status', ['closed', 'resolved'])->count();
        $slaBreached = $this->getTicketSlaBreachCount($filters);

        return [
            'open_ticket_count' => $openTickets,
            'sla_breach_count' => $slaBreached,
        ];
    }

    protected function getRevenueMetrics(array $filters): array
    {
        $query = Deal::query()->where('stage', 'closed_won');
        $this->applyScope($query, $filters);

        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $revenueClosed = (clone $query)->whereBetween('updated_at', [$start, $end])->sum('value');
        $dealsClosed = (clone $query)->whereBetween('updated_at', [$start, $end])->count();

        $allDeals = Deal::query()->whereBetween('created_at', [$start, $end]);
        $this->applyScope($allDeals, $filters);
        $wonDeals = (clone $allDeals)->where('stage', 'closed_won');

        $winRate = $allDeals->count() > 0
            ? round($wonDeals->count() / $allDeals->count() * 100, 2)
            : 0;

        return [
            'revenue_closed_month' => $revenueClosed,
            'deals_closed_month' => $dealsClosed,
            'win_rate' => $winRate,
        ];
    }

    protected function getSystemHealthMetrics(): array
    {
        $jobCount = $this->safeTableCount('jobs');
        $failedJobs = $this->safeTableCount('failed_jobs');
        $lastSchedulerRun = Cache::get('last_scheduler_run');

        return [
            'queue_depth' => $jobCount,
            'failed_jobs' => $failedJobs,
            'last_scheduler_run' => $lastSchedulerRun?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }

    protected function getAdminMetrics(array $filters = []): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        return [
            'contacts_created_month' => Contact::whereBetween('created_at', [$start, $end])->count(),
            'campaign_sends_month' => CampaignRecipient::whereBetween('sent_at', [$start, $end])->count(),
            'active_user_count' => SecurityEvent::where('event_type', 'login_success')
                ->where('created_at', '>=', now()->subDay())
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
        ];
    }

    protected function getTopDeals(array $filters, int $limit = 5): array
    {
        $query = Deal::query()
            ->whereNotIn('stage', ['closed_won', 'closed_lost'])
            ->with(['account', 'owner']);
        $this->applyScope($query, $filters);

        return $query->orderByDesc('value')->limit($limit)->get()
            ->map(fn (Deal $deal) => [
                'id' => $deal->id,
                'title' => $deal->title,
                'value' => $deal->value,
                'stage' => $deal->stage,
                'account' => $deal->account?->name,
                'owner' => $deal->owner?->name,
            ])->toArray();
    }

    protected function getActivityCompletionRate(array $filters): float
    {
        $query = Activity::query();
        $this->applyScope($query, $filters);

        $total = $query->count();
        if ($total === 0) {
            return 0;
        }

        $completed = Activity::query();
        $this->applyScope($completed, $filters);

        return round($completed->whereNotNull('completed_at')->count() / $total * 100, 2);
    }

    protected function getTicketSlaBreachCount(array $filters): int
    {
        $query = Ticket::query();
        $this->applyScope($query, $filters);

        return $query->whereNotNull('sla_breached_at')->count();
    }

    protected function getAgentPerformance(?string $teamId): array
    {
        if (! $teamId) {
            return [];
        }

        return User::query()
            ->whereHas('primaryTeam', fn ($q) => $q->where('team_id', $teamId))
            ->get()
            ->map(fn (User $user) => [
                'user_id' => $user->id,
                'name' => $user->name,
                'tickets_resolved' => Ticket::where('assigned_to', $user->id)
                    ->whereIn('status', ['resolved', 'closed'])
                    ->where('created_at', '>=', now()->subWeek())
                    ->count(),
                'deals_moved_this_week' => Deal::where('owner_id', $user->id)
                    ->where('updated_at', '>=', now()->subWeek())
                    ->count(),
            ])
            ->toArray();
    }

    public function getSegmentPerformance(array $filters = []): array
    {
        $segments = Segment::query()->orderBy('name')->get();

        return $segments->map(function (Segment $segment) use ($filters) {
            $contacts = $this->filteredContactsForSegment($segment->id, $filters);
            $contactIds = $contacts->pluck('id');
            $lastCampaign = Campaign::where('segment_id', $segment->id)
                ->whereNotNull('started_at')
                ->orderByDesc('started_at')
                ->first();
            $engagementRate = 0;

            if ($lastCampaign) {
                $recipients = CampaignRecipient::where('campaign_id', $lastCampaign->id)->count();
                $opened = CampaignRecipient::where('campaign_id', $lastCampaign->id)
                    ->where('status', 'opened')
                    ->count();
                $engagementRate = $recipients > 0 ? round($opened / $recipients * 100, 2) : 0;
            }

            return [
                'id' => $segment->id,
                'name' => $segment->name,
                'contact_count' => $contacts->count(),
                'average_clv' => round((float) ($contacts->avg('clv_score') ?: 0), 2),
                'average_deal_value' => (float) (Deal::whereIn('contact_id', $contactIds)->avg('value') ?: 0),
                'average_csat_score' => round((float) (SurveyResponse::whereIn('contact_id', $contactIds)->avg('score') ?: 0), 2),
                'open_ticket_count' => Ticket::whereIn('contact_id', $contactIds)
                    ->whereNotIn('status', ['closed', 'resolved'])
                    ->count(),
                'campaign_engagement_rate' => $engagementRate,
                'last_campaign' => $lastCampaign ? [
                    'id' => $lastCampaign->id,
                    'name' => $lastCampaign->name,
                    'started_at' => $lastCampaign->started_at?->toIso8601String(),
                ] : null,
            ];
        })->toArray();
    }

    protected function filteredContactsForSegment(string $segmentId, array $filters)
    {
        $query = Contact::query()
            ->where('status', 'active')
            ->whereHas('segments', fn ($q) => $q->where('segments.id', $segmentId));

        $this->applyContactFilters($query, $filters);

        return $query;
    }

    protected function applyContactFilters($query, array $filters): void
    {
        if (isset($filters['contact_type']) && $filters['contact_type'] !== '') {
            $query->where('type', $filters['contact_type']);
        }
        if (isset($filters['loyalty_tier']) && $filters['loyalty_tier'] !== '') {
            $query->where('loyalty_tier', $filters['loyalty_tier']);
        }
        if (isset($filters['owner_id']) && $filters['owner_id'] !== '') {
            $query->where('owner_id', $filters['owner_id']);
        }
        if (isset($filters['team_id']) && $filters['team_id'] !== '') {
            $query->whereHas('owner', fn ($q) => $q->whereHas('primaryTeam', fn ($team) => $team->where('team_id', $filters['team_id'])));
        }
        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['segment_id']) && $filters['segment_id'] !== '') {
            $query->whereHas('segments', fn ($q) => $q->where('segments.id', $filters['segment_id']));
        }
    }

    protected function applyScope($query, array $filters): void
    {
        if (isset($filters['team_id']) && $filters['team_id'] !== '') {
            $model = $query->getModel();

            if (method_exists($model, 'owner')) {
                $query->whereHas('owner', fn ($q) => $q->whereHas('primaryTeam', fn ($team) => $team->where('team_id', $filters['team_id'])));
            } elseif (method_exists($model, 'assignee')) {
                $query->whereHas('assignee', fn ($q) => $q->whereHas('primaryTeam', fn ($team) => $team->where('team_id', $filters['team_id'])));
            }
        }

        if (isset($filters['owner_id']) && $filters['owner_id'] !== '') {
            $model = $query->getModel();

            if (method_exists($model, 'owner')) {
                $query->where('owner_id', $filters['owner_id']);
            } elseif (method_exists($model, 'assignee')) {
                $query->where('assigned_to', $filters['owner_id']);
            }
        }

        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $query->where('created_at', '<=', $filters['date_to']);
        }
    }

    public function getChurnRisk(array $filters = []): array
    {
        $query = Contact::query()
            ->where('status', 'active')
            ->whereNotNull('churn_risk_score')
            ->orderByDesc('churn_risk_score');

        if (isset($filters['contact_type']) && $filters['contact_type'] !== '') {
            $query->where('type', $filters['contact_type']);
        }
        if (isset($filters['loyalty_tier']) && $filters['loyalty_tier'] !== '') {
            $query->where('loyalty_tier', $filters['loyalty_tier']);
        }
        if (isset($filters['owner_id']) && $filters['owner_id'] !== '') {
            $query->where('owner_id', $filters['owner_id']);
        }
        if (isset($filters['team_id']) && $filters['team_id'] !== '') {
            $query->whereHas('owner', fn ($q) => $q->whereHas('primaryTeam', fn ($team) => $team->where('team_id', $filters['team_id'])));
        }
        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['segment_id']) && $filters['segment_id'] !== '') {
            $query->whereHas('segments', fn ($q) => $q->where('segments.id', $filters['segment_id']));
        }

        return $query->with(['account', 'owner', 'tickets', 'surveyResponses', 'clvCalculation' => function ($q) {
            $q->latest('calculated_at');
        }])
            ->limit(100)
            ->get()
            ->map(fn (Contact $contact) => [
                'id' => $contact->id,
                'name' => trim($contact->first_name.' '.$contact->last_name),
                'account' => $contact->account?->name,
                'assigned_agent' => $contact->owner?->name,
                'days_since_last_interaction' => $contact->last_activity_at ? Carbon::parse($contact->last_activity_at)->diffInDays(now()) : null,
                'open_ticket_count' => $contact->tickets->whereNotIn('status', ['closed', 'resolved'])->count(),
                'last_nps_score' => $contact->surveyResponses->sortByDesc('responded_at')->first()?->score,
                'churn_risk_score' => (int) $contact->churn_risk_score,
            ])
            ->toArray();
    }

    public function getCustomerJourneyMap(array $filters = []): array
    {
        $query = Activity::query()->whereNotNull('contact_id');

        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (isset($filters['owner_id']) && $filters['owner_id'] !== '') {
            $query->where('assigned_to', $filters['owner_id']);
        }
        if (isset($filters['team_id']) && $filters['team_id'] !== '') {
            $query->whereHas('assignee', fn ($q) => $q->whereHas('primaryTeam', fn ($team) => $team->where('team_id', $filters['team_id'])));
        }

        return $query->selectRaw('type, count(*) as count, avg(duration_minutes) as average_duration_minutes')
            ->groupBy('type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'stage' => str_replace('_', ' ', ucfirst($row->type)),
                'contact_count' => (int) $row->count,
                'average_duration_minutes' => round((float) ($row->average_duration_minutes ?? 0), 2),
            ])
            ->toArray();
    }

    public function getCohortRetention(array $filters = []): array
    {
        $query = Contact::query();

        if (isset($filters['contact_type']) && $filters['contact_type'] !== '') {
            $query->where('type', $filters['contact_type']);
        }
        if (isset($filters['loyalty_tier']) && $filters['loyalty_tier'] !== '') {
            $query->where('loyalty_tier', $filters['loyalty_tier']);
        }
        if (isset($filters['owner_id']) && $filters['owner_id'] !== '') {
            $query->where('owner_id', $filters['owner_id']);
        }
        if (isset($filters['team_id']) && $filters['team_id'] !== '') {
            $query->whereHas('owner', fn ($q) => $q->whereHas('primaryTeam', fn ($team) => $team->where('team_id', $filters['team_id'])));
        }
        if (isset($filters['date_from']) && $filters['date_from'] !== '') {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to']) && $filters['date_to'] !== '') {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $contacts = $query->with(['interactions' => function ($q) {
            $q->select('id', 'contact_id', 'created_at');
        }])->get();

        $cohortsByMonth = $contacts->groupBy(fn (Contact $contact) => $contact->created_at?->format('Y-m'));
        $cohorts = [];

        foreach ($cohortsByMonth as $month => $cohortContacts) {
            $total = $cohortContacts->count();
            $retention = [];

            foreach ([1, 3, 6, 12] as $monthOffset) {
                $active = $cohortContacts->filter(function (Contact $contact) use ($monthOffset) {
                    if (! $contact->created_at) {
                        return false;
                    }

                    $cohortStart = Carbon::parse($contact->created_at)->addMonthsNoOverflow($monthOffset);
                    $cohortEnd = $cohortStart->copy()->addMonthNoOverflow();

                    return $contact->interactions->contains(function ($interaction) use ($cohortStart, $cohortEnd) {
                        $interactionDate = Carbon::parse($interaction->created_at);
                        return $interactionDate->greaterThanOrEqualTo($cohortStart) && $interactionDate->lessThan($cohortEnd);
                    });
                })->count();

                $retention["month_{$monthOffset}"] = $total > 0 ? round(($active / $total) * 100, 2) : 0;
            }

            $cohorts[] = [
                'cohort_month' => $month,
                'cohort_size' => $total,
                ...$retention,
            ];
        }

        return collect($cohorts)->sortByDesc('cohort_month')->values()->all();
    }

    protected function safeTableCount(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}

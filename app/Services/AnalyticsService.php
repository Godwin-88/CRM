<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Interaction;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getDashboardMetrics(string $role, ?int $userId = null, ?int $teamId = null): array
    {
        $cacheKey = "dashboard_metrics_{$role}_{$userId}_{$teamId}";

        return Cache::remember($cacheKey, 900, function () use ($role, $userId, $teamId) {
            $filters = $this->buildTeamFilters($teamId, $userId);

            $metrics = [
                'period' => 'current_month',
                'generated_at' => now()->toIso8601String(),
            ];

            if (in_array($role, ['admin', 'manager', 'agent'])) {
                $metrics['pipeline'] = $this->getPipelineMetrics($filters);
                $metrics['activity'] = $this->getActivityMetrics($filters);
                $metrics['tickets'] = $this->getTicketMetrics($filters);
                $metrics['revenue'] = $this->getRevenueMetrics($filters);
                $metrics['system_health'] = $this->getSystemHealthMetrics();
            }

            return $metrics;
        });
    }

    protected function buildTeamFilters(?int $teamId, ?int $userId): array
    {
        $filters = [];

        if ($teamId) {
            $filters['team_id'] = $teamId;
        }

        if ($userId && ! $teamId) {
            $filters['owner_id'] = $userId;
        }

        return $filters;
    }

    protected function getPipelineMetrics(array $filters): array
    {
        $query = Deal::query()->whereNotIn('stage', ['closed_won', 'closed_lost']);

        if (isset($filters['team_id'])) {
            $query->whereHas('owner', fn ($q) => $q->where('team_id', $filters['team_id']));
        }
        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        $openDeals = $query->get();
        $totalValue = $openDeals->sum('value');
        $weightedValue = $openDeals->sum(fn ($d) => $d->value * $d->probability / 100);

        $byStage = $openDeals->groupBy('stage')->map(fn ($deals, $stage) => [
            'stage' => $stage,
            'count' => $deals->count(),
            'value' => $deals->sum('value'),
            'weighted_value' => $deals->sum(fn ($d) => $d->value * $d->probability / 100),
        ])->values();

        $recentInteractions = Interaction::when(isset($filters['team_id']), fn ($q) => $q->whereHas('agent', fn ($qq) => $qq->where('team_id', $filters['team_id']))
        )->when(isset($filters['owner_id']), fn ($q) => $q->where('agent_id', $filters['owner_id'])
        )->latest()->take(5)->get();

        return [
            'open_deal_count' => $openDeals->count(),
            'open_deal_value' => $totalValue,
            'weighted_pipeline_value' => $weightedValue,
            'by_stage' => $byStage,
            'recent_interactions' => $recentInteractions->map(fn ($i) => [
                'id' => $i->id,
                'type' => $i->type,
                'direction' => $i->direction,
                'created_at' => $i->created_at,
            ]),
        ];
    }

    protected function getActivityMetrics(array $filters): array
    {
        $query = Activity::query();

        if (isset($filters['team_id'])) {
            $query->whereHas('assignedTo', fn ($q) => $q->where('team_id', $filters['team_id']));
        }
        if (isset($filters['owner_id'])) {
            $query->where('assigned_to', $filters['owner_id']);
        }

        $today = Carbon::today();
        $dueToday = $query->whereDate('due_at', $today)->whereNull('completed_at')->count();
        $overdue = $query->where('due_at', '<', $today)->whereNull('completed_at')->count();

        return [
            'due_today' => $dueToday,
            'overdue' => $overdue,
        ];
    }

    protected function getTicketMetrics(array $filters): array
    {
        $query = Ticket::query();

        if (isset($filters['team_id'])) {
            $query->whereHas('assignee', fn ($q) => $q->where('team_id', $filters['team_id']));
        }
        if (isset($filters['owner_id'])) {
            $query->where('assignee_id', $filters['owner_id']);
        }

        $openTickets = $query->whereNotIn('status', ['closed', 'resolved'])->count();
        $slaBreached = $query->whereNotNull('sla_breached_at')->count();

        return [
            'open_ticket_count' => $openTickets,
            'sla_breach_count' => $slaBreached,
        ];
    }

    protected function getRevenueMetrics(array $filters): array
    {
        $query = Deal::query()->where('stage', 'closed_won')
            ->whereMonth('updated_at', Carbon::now()->month);

        if (isset($filters['team_id'])) {
            $query->whereHas('owner', fn ($q) => $q->where('team_id', $filters['team_id']));
        }
        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        $revenueClosed = $query->sum('value');
        $dealsClosed = $query->count();

        $wonDeals = Deal::query()->where('stage', 'closed_won')
            ->whereMonth('updated_at', Carbon::now()->month);

        $allDeals = Deal::query()->whereMonth('created_at', Carbon::now()->month);
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
        $jobCount = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        $lastSchedulerRun = Cache::get('last_scheduler_run', now()->toIso8601String());

        return [
            'queue_depth' => $jobCount,
            'failed_jobs' => $failedJobs,
            'last_scheduler_run' => $lastSchedulerRun,
        ];
    }

    public function getCohortRetention(): array
    {
        $cohorts = [];

        for ($month = 1; $month <= 12; $month++) {
            $contacts = Contact::where('created_at', '<=', now()->subMonths($month))
                ->where('created_at', '>', now()->subMonths($month + 1))
                ->get();

            $total = $contacts->count();
            $active = $contacts->filter(fn ($c) => $c->interactions()->exists())->count();

            $cohorts[] = [
                'month' => $month,
                'cohort_size' => $total,
                'active_count' => $active,
                'retention_rate' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            ];
        }

        return $cohorts;
    }
}

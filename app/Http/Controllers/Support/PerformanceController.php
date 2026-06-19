<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketRating;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PerformanceController extends Controller
{
    public function index(Request $request)
    {
        $dateRange = $this->getDateRange($request->input('range', 'last_30_days'), $request->input('custom_start'), $request->input('custom_end'));
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        $teamId = $request->input('team_id');

        $teams = Team::orderBy('name')->get(['id', 'name']);
        $categories = \App\Models\TicketCategory::active()->get(['id', 'name']);

        $agentMetrics = $this->calculateAgentMetrics($startDate, $endDate, $teamId);
        $teamMetrics = $this->calculateTeamMetrics($startDate, $endDate, $teamId);

        return Inertia::render('Support/Performance/Index', [
            'agentMetrics' => $agentMetrics,
            'teamMetrics' => $teamMetrics,
            'teams' => $teams,
            'categories' => $categories,
            'filters' => $request->only(['range', 'team_id', 'custom_start', 'custom_end']),
        ]);
    }

    public function export(Request $request)
    {
        $dateRange = $this->getDateRange($request->input('range', 'last_30_days'), $request->input('custom_start'), $request->input('custom_end'));
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $agentMetrics = $this->calculateAgentMetrics($startDate, $endDate);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="agent-performance-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.csv"',
        ];

        $callback = function () use ($agentMetrics) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Agent', 'Tickets Created', 'Tickets Resolved', 'Tickets Closed', 'Avg First Response (hrs)', 'Avg Resolution (hrs)', 'SLA Breaches', 'Breach Rate %', 'Avg CSAT']);

            foreach ($agentMetrics as $metric) {
                fputcsv($file, [
                    $metric['agent_name'],
                    $metric['tickets_created'],
                    $metric['tickets_resolved'],
                    $metric['tickets_closed'],
                    $metric['avg_first_response_hours'],
                    $metric['avg_resolution_hours'],
                    $metric['sla_breach_count'],
                    $metric['sla_breach_rate'].'%',
                    $metric['avg_csat_score'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function getDateRange(string $range, ?string $customStart = null, ?string $customEnd = null): array
    {
        return match ($range) {
            'today' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'yesterday' => [
                'start' => now()->subDay()->startOfDay(),
                'end' => now()->subDay()->endOfDay(),
            ],
            'last_7_days' => [
                'start' => now()->subDays(7)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'last_30_days' => [
                'start' => now()->subDays(30)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'this_month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            'last_month' => [
                'start' => now()->subMonth()->startOfMonth(),
                'end' => now()->subMonth()->endOfMonth(),
            ],
            'custom' => [
                'start' => $customStart ? Carbon::parse($customStart)->startOfDay() : now()->subDays(30)->startOfDay(),
                'end' => $customEnd ? Carbon::parse($customEnd)->endOfDay() : now()->endOfDay(),
            ],
            default => [
                'start' => now()->subDays(30)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
        };
    }

    protected function calculateAgentMetrics(Carbon $startDate, Carbon $endDate, ?string $teamId = null): array
    {
        $agents = User::role('agent')
            ->when($teamId, fn ($q) => $q->whereHas('teamMembers', fn ($q) => $q->where('team_id', $teamId)))
            ->get(['id', 'name', 'email']);

        $metrics = [];

        foreach ($agents as $agent) {
            $createdTickets = Ticket::where('created_by', $agent->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->with('slaInstance')
                ->get();

            $resolvedTickets = Ticket::where('assigned_to', $agent->id)
                ->where('status', 'resolved')
                ->whereBetween('resolved_at', [$startDate, $endDate])
                ->with('slaInstance')
                ->get();

            $closedTickets = Ticket::where('assigned_to', $agent->id)
                ->where('status', 'closed')
                ->whereBetween('closed_at', [$startDate, $endDate])
                ->get();

            $avgFirstResponse = $this->calculateAvgFirstResponse($createdTickets);
            $avgResolution = $resolvedTickets->avg(fn ($t) => $t->created_at->diffInMinutes($t->resolved_at)) / 60;

            $breachedCount = $createdTickets->filter(fn ($t) => $t->slaInstance &&
                ($t->slaInstance->first_response_breached || $t->slaInstance->resolution_breached))->count();

            $csatScore = TicketRating::whereHas('ticket', fn ($q) => $q->where('assigned_to', $agent->id)
                ->whereBetween('resolved_at', [$startDate, $endDate]))->avg('score');

            $metrics[] = [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'agent_email' => $agent->email,
                'tickets_created' => $createdTickets->count(),
                'tickets_resolved' => $resolvedTickets->count(),
                'tickets_closed' => $closedTickets->count(),
                'avg_first_response_hours' => round($avgFirstResponse, 2),
                'avg_resolution_hours' => round($avgResolution, 2),
                'sla_breach_count' => $breachedCount,
                'sla_breach_rate' => $createdTickets->count() > 0 ? round(($breachedCount / $createdTickets->count()) * 100, 2) : 0,
                'avg_csat_score' => $csatScore ? round($csatScore, 2) : 0,
            ];
        }

        return $metrics;
    }

    protected function calculateTeamMetrics(Carbon $startDate, Carbon $endDate, ?string $teamId = null): array
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->with(['slaInstance', 'rating', 'assignee']);

        if ($teamId) {
            $tickets->whereHas('assignee.teamMembers', fn ($q) => $q->where('team_id', $teamId));
        }

        $tickets = $tickets->get();

        $totalCreated = $tickets->count();
        $totalResolved = $tickets->where('status', 'resolved')->count();
        $totalClosed = $tickets->where('status', 'closed')->count();

        $breachedCount = $tickets->filter(fn ($t) => $t->slaInstance &&
            ($t->slaInstance->first_response_breached || $t->slaInstance->resolution_breached))->count();

        $avgCsat = TicketRating::whereHas('ticket', fn ($q) => $q->whereBetween('resolved_at', [$startDate, $endDate]))
            ->avg('score');

        $previousStart = (clone $startDate)->subDays($endDate->diffInDays($startDate));
        $previousEnd = (clone $startDate)->subDay();

        $previousTickets = Ticket::whereBetween('created_at', [$previousStart, $previousEnd])->get();
        $previousBreached = $previousTickets->filter(fn ($t) => $t->slaInstance &&
            ($t->slaInstance->first_response_breached || $t->slaInstance->resolution_breached))->count();

        return [
            'tickets_created' => $totalCreated,
            'tickets_resolved' => $totalResolved,
            'tickets_closed' => $totalClosed,
            'sla_breach_count' => $breachedCount,
            'sla_breach_rate' => $totalCreated > 0 ? round(($breachedCount / $totalCreated) * 100, 2) : 0,
            'avg_csat_score' => $avgCsat ? round($avgCsat, 2) : 0,
            'trends' => [
                'tickets_created_change' => $this->calculateTrend($totalCreated, $previousTickets->count()),
                'sla_breach_rate_change' => $this->calculateTrend(
                    $totalCreated > 0 ? ($breachedCount / $totalCreated) * 100 : 0,
                    $previousTickets->count() > 0 ? ($previousBreached / $previousTickets->count()) * 100 : 0
                ),
            ],
        ];
    }

    protected function calculateAvgFirstResponse($tickets): float
    {
        $times = $tickets->map(fn ($t) => $t->slaInstance && $t->slaInstance->first_response_met_at
            ? $t->slaInstance->assigned_at->diffInMinutes($t->slaInstance->first_response_met_at) / 60
            : null)->filter()->values();

        return $times->count() > 0 ? $times->avg() : 0;
    }

    protected function calculateTrend(float $current, float $previous): array
    {
        if ($previous == 0) {
            return ['direction' => $current > 0 ? 'up' : 'neutral', 'percent' => $current > 0 ? 100 : 0];
        }

        $change = round((($current - $previous) / $previous) * 100, 1);

        return [
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
            'percent' => abs($change),
        ];
    }
}

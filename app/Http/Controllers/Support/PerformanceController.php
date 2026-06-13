<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketRating;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PerformanceController extends Controller
{
    public function index(Request $request)
    {
        $dateRange = $this->getDateRange($request->input('range', 'last_30_days'));
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $metrics = $this->calculateMetrics($startDate, $endDate);

        return Inertia::render('Support/Performance/Index', [
            'metrics' => $metrics,
            'filters' => $request->only(['range']),
        ]);
    }

    protected function getDateRange(string $range): array
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
            default => [
                'start' => now()->subDays(30)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
        };
    }

    protected function calculateMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->with('slaInstance', 'rating')
            ->get();

        $resolvedTickets = $tickets->where('status', 'resolved');
        $closedTickets = $tickets->where('status', 'closed');

        $totalCreated = $tickets->count();
        $totalResolved = $resolvedTickets->count();
        $totalClosed = $closedTickets->count();

        $avgFirstResponse = $tickets->avg(fn ($t) => $t->slaInstance?->first_response_met_at
            ? $t->slaInstance->assigned_at->diffInMinutes($t->slaInstance->first_response_met_at) / 60
            : null);

        $avgResolution = $resolvedTickets->avg(fn ($t) => $t->resolved_at
            ? $t->created_at->diffInMinutes($t->resolved_at) / 60
            : null);

        $breachedTickets = $tickets->filter(fn ($t) => $t->slaInstance &&
            ($t->slaInstance->first_response_breached || $t->slaInstance->resolution_breached));

        $breachRate = $totalCreated > 0 ? ($breachedTickets->count() / $totalCreated) * 100 : 0;

        $avgCsat = TicketRating::whereHas('ticket', fn ($q) => $q->whereBetween('resolved_at', [$startDate, $endDate]))
            ->avg('score');

        return [
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'tickets_created' => $totalCreated,
            'tickets_resolved' => $totalResolved,
            'tickets_closed' => $totalClosed,
            'avg_first_response_hours' => round($avgFirstResponse, 2),
            'avg_resolution_hours' => round($avgResolution, 2),
            'sla_breach_count' => $breachedTickets->count(),
            'sla_breach_rate' => round($breachRate, 2),
            'avg_csat_score' => round($avgCsat, 2),
        ];
    }
}

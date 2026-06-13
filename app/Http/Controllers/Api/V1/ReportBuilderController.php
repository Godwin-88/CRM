<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ReportDefinition;
use App\Models\ScheduledReport;
use App\Services\ExploratoryAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportBuilderController extends Controller
{
    public function __construct(protected ExploratoryAnalyticsService $explorer) {}

    public function index(Request $request): JsonResponse
    {
        $query = ReportDefinition::with('owner')->query();

        if ($request->user()->hasRole('agent')) {
            $query->where('owner_id', $request->user()->id);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'in:private,shared',
            'entity_type' => 'required|string',
            'filters' => 'nullable|array',
            'fields' => 'nullable|array',
            'sort_field' => 'nullable|string',
            'sort_direction' => 'in:asc,desc',
            'group_by' => 'nullable|string',
            'chart_type' => 'nullable|in:bar,line,pie,table',
        ]);

        $report = ReportDefinition::create([
            ...$validated,
            'owner_id' => $request->user()->id,
            'visibility' => $validated['visibility'] ?? 'private',
        ]);

        return response()->json($report->load('owner'), 201);
    }

    public function show(ReportDefinition $report): JsonResponse
    {
        $this->authorize('view', $report);

        $data = $this->explorer->runReport($report);

        return response()->json([
            'report' => $report->load('owner'),
            'data' => $data,
        ]);
    }

    public function update(Request $request, ReportDefinition $report): JsonResponse
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'in:private,shared',
            'filters' => 'nullable|array',
            'fields' => 'nullable|array',
            'sort_field' => 'nullable|string',
            'sort_direction' => 'in:asc,desc',
            'group_by' => 'nullable|string',
            'chart_type' => 'nullable|in:bar,line,pie,table',
        ]);

        $report->update($validated);

        return response()->json($report);
    }

    public function destroy(ReportDefinition $report): JsonResponse
    {
        $this->authorize('delete', $report);

        $report->delete();

        return response()->json(['deleted' => true]);
    }

    public function schedule(Request $request, ReportDefinition $report): JsonResponse
    {
        $this->authorize('view', $report);

        $validated = $request->validate([
            'frequency' => 'required|in:daily,weekly,monthly',
            'day_of_week' => 'required_if:frequency,weekly|integer|min:1|max:7',
            'day_of_month' => 'required_if:frequency,monthly|integer|min:1|max:28',
            'recipients' => 'required|array',
            'recipients.*' => 'email',
        ]);

        $scheduledReport = ScheduledReport::create([
            'report_id' => $report->id,
            'frequency' => $validated['frequency'],
            'day_of_week' => $validated['day_of_week'] ?? null,
            'day_of_month' => $validated['day_of_month'] ?? null,
            'recipients' => $validated['recipients'],
            'next_run_at' => $this->calculateNextRun($validated['frequency'], $validated['day_of_week'] ?? null, $validated['day_of_month'] ?? null),
        ]);

        return response()->json($scheduledReport, 201);
    }

    protected function calculateNextRun(string $frequency, ?int $dayOfWeek, ?int $dayOfMonth): ?\DateTime
    {
        $next = match ($frequency) {
            'daily' => Carbon::now()->addDay(),
            'weekly' => Carbon::now()->next($dayOfWeek ?? Carbon::MONDAY),
            'monthly' => Carbon::now()->addMonth()->setDay($dayOfMonth ?? 1),
            default => Carbon::now()->addDay(),
        };

        return $next;
    }
}

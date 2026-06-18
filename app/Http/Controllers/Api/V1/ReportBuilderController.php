<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverScheduledReport;
use App\Models\ReportDefinition;
use App\Models\ScheduledReport;
use App\Services\ExploratoryAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Writer;

class ReportBuilderController extends Controller
{
    public function __construct(protected ExploratoryAnalyticsService $explorer) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ReportDefinition::class);

        $query = ReportDefinition::with('owner')->query();

        if ($request->user()->hasRole('agent')) {
            abort(403);
        }

        if (! $request->user()->hasRole('admin')) {
            $query->where(fn ($q) => $q->where('owner_id', $request->user()->id)->orWhere('visibility', 'shared'));
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->hasRole('agent')) {
            abort(403);
        }

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
            'data' => $data['rows'],
            'row_count' => $data['row_count'],
            'background' => $data['background'],
        ]);
    }

    public function update(Request $request, ReportDefinition $report): JsonResponse
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'in:private,shared',
            'entity_type' => 'sometimes|string',
            'filters' => 'nullable|array',
            'fields' => 'nullable|array',
            'sort_field' => 'nullable|string',
            'sort_direction' => 'in:asc,desc',
            'group_by' => 'nullable|string',
            'chart_type' => 'nullable|in:bar,line,pie,table',
        ]);

        $report->update($validated);

        return response()->json($report->load('owner'));
    }

    public function destroy(ReportDefinition $report): JsonResponse
    {
        $this->authorize('delete', $report);

        $report->delete();

        return response()->json(['deleted' => true]);
    }

    public function schedule(Request $request, ReportDefinition $report): JsonResponse
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'frequency' => 'required|in:daily,weekly,monthly',
            'day_of_week' => 'required_if:frequency,weekly|integer|min:1|max:7',
            'day_of_month' => 'required_if:frequency,monthly|integer|min:1|max:28',
            'recipients' => 'required|array|min:1',
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

    public function run(ReportDefinition $report): JsonResponse
    {
        $this->authorize('view', $report);

        $data = $this->explorer->runReport($report);

        if ($data['background']) {
            return response()->json([
                'queued' => true,
                'message' => 'Report is being generated in the background.',
            ], 202);
        }

        return response()->json([
            'report' => $report,
            'data' => $data['rows'],
            'row_count' => $data['row_count'],
        ]);
    }

    public function exportCsv(ReportDefinition $report): \Illuminate\Http\Response
    {
        $this->authorize('view', $report);

        $data = $this->explorer->runReport($report)['rows'];
        $csv = Writer::createFromString('');

        if (isset($data[0]) && is_array($data[0])) {
            $csv->insertOne(array_keys($data[0]));
            foreach ($data as $row) {
                $csv->insertOne(array_values($row));
            }
        }

        return response($csv->getContent(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($report->name, '_').'.csv"',
        ]);
    }

    public function exportPdf(ReportDefinition $report): \Illuminate\Http\Response
    {
        $this->authorize('view', $report);

        $data = $this->explorer->runReport($report)['rows'];

        $pdf = Pdf::loadHTML($this->renderPdfHtml($report, $data, now()));

        return $pdf->download(Str::slug($report->name, '_').'.pdf');
    }

    public function deliver(ScheduledReport $scheduledReport): JsonResponse
    {
        $this->authorize('update', $scheduledReport->report);

        DeliverScheduledReport::dispatch($scheduledReport);

        return response()->json(['queued' => true]);
    }

    protected function renderPdfHtml(ReportDefinition $report, array $rows, \DateTimeInterface $generatedAt): string
    {
        $headers = isset($rows[0]) && is_array($rows[0]) ? array_keys($rows[0]) : [];
        $body = '';

        foreach ($rows as $row) {
            $body .= '<tr>';
            foreach ($headers as $header) {
                $body .= '<td>'.htmlspecialchars((string) ($row[$header] ?? ''), ENT_QUOTES, 'UTF-8').'</td>';
            }
            $body .= '</tr>';
        }

        $headerRow = '';
        foreach ($headers as $header) {
            $headerRow .= '<th>'.htmlspecialchars($header, ENT_QUOTES, 'UTF-8').'</th>';
        }

        return <<<HTML
<!doctype html>
<html>
<head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,sans-serif}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px}th{background:#f3f4f6}</style></head>
<body>
<h1>{$report->name}</h1>
<p>Generated: {$generatedAt->toDateTimeString()}</p>
<table>{$headerRow}{$body}</table>
</body>
</html>
HTML;
    }

    protected function calculateNextRun(string $frequency, ?int $dayOfWeek, ?int $dayOfMonth): Carbon
    {
        return match ($frequency) {
            'daily' => Carbon::now()->addDay(),
            'weekly' => Carbon::now()->next($dayOfWeek ?? Carbon::MONDAY),
            'monthly' => Carbon::now()->addMonth()->setDay($dayOfMonth ?? 1),
            default => Carbon::now()->addDay(),
        };
    }
}

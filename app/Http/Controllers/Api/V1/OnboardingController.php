<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OnboardingRecord;
use App\Models\OnboardingTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = OnboardingRecord::query()->with(['template', 'contact', 'account', 'enroledBy']);

        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->contact_id);
        }
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function templates(Request $request): JsonResponse
    {
        $query = OnboardingTemplate::query()->with('creator');

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $this->authorize('create', OnboardingTemplate::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'steps' => 'required|array',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.description' => 'nullable|string',
            'steps.*.assigned_role' => 'required|string|max:100',
            'steps.*.due_date_offset_days' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $template = OnboardingTemplate::create($validated);

        return response()->json($template->load('creator'), 201);
    }

    public function updateTemplate(Request $request, OnboardingTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'steps' => 'sometimes|array',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.description' => 'nullable|string',
            'steps.*.assigned_role' => 'required|string|max:100',
            'steps.*.due_date_offset_days' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $template->update($validated);

        return response()->json($template);
    }

    public function enroll(Request $request, $contactOrAccountId): JsonResponse
    {
        $this->authorize('create', OnboardingRecord::class);

        $validated = $request->validate([
            'template_id' => 'required|exists:onboarding_templates,id',
            'is_contact' => 'required|boolean',
        ]);

        $template = OnboardingTemplate::findOrFail($validated['template_id']);

        if ($validated['is_contact']) {
            $contact = \App\Models\Contact::findOrFail($contactOrAccountId);
            $record = OnboardingRecord::create([
                'template_id' => $template->id,
                'contact_id' => $contact->id,
                'status' => 'in_progress',
                'percentage_complete' => 0,
                'enrolled_at' => now(),
                'enroled_by' => auth()->id(),
            ]);
        } else {
            $account = \App\Models\Account::findOrFail($contactOrAccountId);
            $record = OnboardingRecord::create([
                'template_id' => $template->id,
                'account_id' => $account->id,
                'status' => 'in_progress',
                'percentage_complete' => 0,
                'enrolled_at' => now(),
                'enroled_by' => auth()->id(),
            ]);
        }

        // Create activities for each step
        $this->createStepActivities($record, $template);

        // Trigger welcome email sequence if available
        $this->triggerWelcomeSequence($record);

        activity()
            ->performedOn($record)
            ->causedBy(auth()->user())
            ->withProperties(['template_id' => $template->id])
            ->event('onboarding_enrolled')
            ->log('Onboarding enrolment created');

        return response()->json($record->load(['template', 'activities']), 201);
    }

    public function completeStep(Request $request, OnboardingRecord $record, $activityId): JsonResponse
    {
        $this->authorize('update', $record);

        $validated = $request->validate([
            'completion_note' => 'required|string|max:2000',
        ]);

        $activity = $record->activities()->findOrFail($activityId);
        $activity->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_note' => $validated['completion_note'],
        ]);

        // Recalculate percentage
        $totalSteps = count($record->template->steps ?? []);
        $completedSteps = $record->activities()->where('status', 'completed')->count();
        $percentage = $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0;

        $record->update(['percentage_complete' => $percentage]);

        if ($percentage === 100) {
            $record->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Auto-advance contact from prospect to customer if applicable
            if ($record->contact_id) {
                $contact = \App\Models\Contact::find($record->contact_id);
                if ($contact && $contact->type === 'prospect') {
                    $contact->update(['type' => 'customer']);
                }
            }
        }

        return response()->json(['message' => 'Step marked as complete.']);
    }

    public function show(OnboardingRecord $record): JsonResponse
    {
        $this->authorize('view', $record);
        return response()->json($record->load(['template', 'contact', 'account', 'activities.assignedTo']));
    }

    private function createStepActivities($record, $template): void
    {
        $steps = $template->steps ?? [];

        foreach ($steps as $index => $step) {
            $dueDateOffset = $step['due_date_offset_days'] ?? 0;
            $dueDate = now()->addDays($dueDateOffset)->toDateString();

            $record->activities()->create([
                'template_step_id' => $index,
                'name' => $step['name'],
                'description' => $step['description'] ?? null,
                'assigned_role' => $step['assigned_role'],
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);
        }
    }

    private function triggerWelcomeSequence($record): void
    {
        // Integration hook: trigger welcome email sequence from campaign module
        // This is implemented as a queued job
        \App\Jobs\TriggerWelcomeSequence::dispatch($record);
    }

    public function analytics(): JsonResponse
    {
        $this->authorize('viewAny', OnboardingTemplate::class);

        $records = OnboardingRecord::with('template')->get();

        $templates = OnboardingTemplate::all()->map(function ($template) use ($records) {
            $templateRecords = $records->where('template_id', $template->id);
            $total = $templateRecords->count();
            $completed = $templateRecords->where('status', 'completed')->count();

            return [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'total_enrolled' => $total,
                'completed' => $completed,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                'avg_completion_days' => $this->calculateAvgCompletionDays($templateRecords),
            ];
        });

        return response()->json([
            'templates' => $templates,
            'total_active' => $records->where('status', 'in_progress')->count(),
            'total_completed' => $records->where('status', 'completed')->count(),
        ]);
    }

    private function calculateAvgCompletionDays($records): ?float
    {
        $completed = $records->where('status', 'completed')->filter(fn($r) => $r->enrolled_at && $r->completed_at);

        if ($completed->isEmpty()) {
            return null;
        }

        $totalDays = $completed->sum(fn($r) => $r->enrolled_at->diffInDays($r->completed_at));

        return round($totalDays / $completed->count(), 2);
    }
}

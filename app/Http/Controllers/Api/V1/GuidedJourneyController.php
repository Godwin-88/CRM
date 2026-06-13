<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyAgentOfJourneyCompletion;
use App\Models\GuidedJourney;
use App\Models\JourneyCompletion;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuidedJourneyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GuidedJourney::query()->with('creator');

        if ($request->filled('is_published')) {
            $query->where('is_published', $request->boolean('is_published'));
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', GuidedJourney::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:guided_journeys,slug',
            'description' => 'nullable|string',
            'steps' => 'required|array',
            'steps.*.title' => 'required|string|max:255',
            'steps.*.content' => 'required|string',
            'steps.*.action_type' => 'required|in:info,form,download,link',
            'steps.*.form_config' => 'nullable|array',
            'steps.*.creates_ticket' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'notify_agent_on_completion' => 'sometimes|boolean',
        ]);

        $validated['created_by'] = auth()->id();

        $journey = GuidedJourney::create($validated);

        return response()->json($journey->load('creator'), 201);
    }

    public function show(GuidedJourney $journey): JsonResponse
    {
        return response()->json($journey->load('creator', 'completions.contact'));
    }

    public function update(Request $request, GuidedJourney $journey): JsonResponse
    {
        $this->authorize('update', $journey);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:guided_journeys,slug,'.$journey->id,
            'description' => 'nullable|string',
            'steps' => 'sometimes|array',
            'steps.*.title' => 'required|string|max:255',
            'steps.*.content' => 'required|string',
            'steps.*.action_type' => 'required|in:info,form,download,link',
            'steps.*.form_config' => 'nullable|array',
            'steps.*.creates_ticket' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'notify_agent_on_completion' => 'sometimes|boolean',
        ]);

        $journey->update($validated);

        return response()->json($journey);
    }

    public function destroy(GuidedJourney $journey): JsonResponse
    {
        $this->authorize('delete', $journey);
        $journey->delete();

        return response()->json(null, 204);
    }

    public function analytics(): JsonResponse
    {
        $this->authorize('viewAny', GuidedJourney::class);

        $journeys = GuidedJourney::withCount('completions')->get()->map(function ($journey) {
            $completions = $journey->completions()->get();
            $completed = $completions->where('is_completed', true)->count();

            return [
                'journey_id' => $journey->id,
                'name' => $journey->name,
                'is_published' => $journey->is_published,
                'total_starts' => $journey->completions_count,
                'total_completions' => $completed,
                'completion_rate' => $journey->completions_count > 0 ? round(($completed / $journey->completions_count) * 100, 2) : 0,
            ];
        });

        return response()->json(['journeys' => $journeys]);
    }

    // Portal: start journey
    public function start(Request $request, $slug): JsonResponse
    {
        $journey = GuidedJourney::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $contactId = $request->input('contact_id');

        if (! $contactId) {
            return response()->json(['message' => 'Contact ID is required.'], 422);
        }

        $completion = JourneyCompletion::create([
            'journey_id' => $journey->id,
            'contact_id' => $contactId,
            'inputs' => [],
            'is_completed' => false,
        ]);

        return response()->json([
            'journey' => $journey,
            'completion_id' => $completion->id,
            'current_step_index' => 0,
            'total_steps' => count($journey->steps ?? []),
        ]);
    }

    // Portal: submit step input
    public function submitStep(Request $request, JourneyCompletion $completion, $stepIndex): JsonResponse
    {
        $journey = $completion->journey;

        if ($stepIndex >= count($journey->steps ?? [])) {
            return response()->json(['message' => 'Invalid step index.'], 422);
        }

        $stepData = $request->validate([
            'inputs' => 'nullable|array',
        ]);

        $inputs = $completion->inputs ?? [];
        $inputs[$stepIndex] = $stepData['inputs'] ?? [];
        $completion->update(['inputs' => $inputs]);

        // Check if this is the last step
        $isLast = ($stepIndex === count($journey->steps) - 1);

        if ($isLast) {
            $completion->update(['is_completed' => true, 'completed_at' => now()]);

            // If step creates a ticket, create it
            $step = $journey->steps[$stepIndex];
            if (($step['creates_ticket'] ?? false) && ! empty($inputs[$stepIndex])) {
                Ticket::create([
                    'contact_id' => $completion->contact_id,
                    'subject' => 'Journey completion: '.$journey->name,
                    'description' => json_encode($inputs[$stepIndex]),
                    'status' => 'open',
                ]);
            }

            // Notify agent if enabled
            if ($journey->notify_agent_on_completion) {
                $contact = $completion->contact;
                if ($contact->owner_id) {
                    NotifyAgentOfJourneyCompletion::dispatch($completion, $contact->owner_id);
                }
            }

            activity()
                ->performedOn($completion->contact)
                ->withProperties(['journey_id' => $journey->id])
                ->event('journey_completed')
                ->log('Contact completed guided journey');
        }

        return response()->json([
            'completion' => $completion,
            'next_step_index' => $isLast ? null : $stepIndex + 1,
            'is_completed' => $isLast,
        ]);
    }
}

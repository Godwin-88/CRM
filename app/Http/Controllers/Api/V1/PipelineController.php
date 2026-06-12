<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Deal;
use App\Models\DealAutomation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PipelineController extends Controller
{
    public function index(): JsonResponse
    {
        $pipelines = Pipeline::with('stages')->get();
        return response()->json($pipelines);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'sometimes|boolean',
            'stages' => 'required|array|min:1',
            'stages.*.name' => 'required|string',
            'stages.*.probability' => 'required|integer|min:0|max:100',
            'stages.*.description' => 'nullable|string',
        ]);

        DB::transaction(function () use (&$pipeline, $validated) {
            if ($validated['is_default'] ?? false) {
                Pipeline::where('is_default', true)->update(['is_default' => false]);
            }

            $pipeline = Pipeline::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'is_default' => $validated['is_default'] ?? false,
            ]);

            foreach ($validated['stages'] as $index => $stage) {
                $pipeline->stages()->create([
                    'name' => $stage['name'],
                    'probability' => $stage['probability'],
                    'description' => $stage['description'] ?? null,
                    'position' => $index + 1,
                ]);
            }

            activity()
                ->causedBy(auth()->user())
                ->withProperties(['pipeline_id' => $pipeline->id, 'stages' => $validated['stages']])
                ->event('created')
                ->log('Pipeline created');
        });

        return response()->json($pipeline->load('stages'), 201);
    }

    public function show(Pipeline $pipeline): JsonResponse
    {
        $pipeline->load('stages');
        return response()->json($pipeline);
    }

    public function update(Request $request, Pipeline $pipeline): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'sometimes|boolean',
            'stages' => 'sometimes|array',
            'stages.*.id' => 'nullable|exists:pipeline_stages,id',
            'stages.*.name' => 'required|string',
            'stages.*.probability' => 'required|integer|min:0|max:100',
            'stages.*.description' => 'nullable|string',
            'stages.*.position' => 'required|integer',
        ]);

        DB::transaction(function () use ($pipeline, $validated) {
            if (isset($validated['is_default']) && $validated['is_default']) {
                Pipeline::where('is_default', true)->where('id', '!=', $pipeline->id)->update(['is_default' => false]);
            }

            if (isset($validated['name'])) {
                $pipeline->update(['name' => $validated['name']]);
            }
            if (isset($validated['description'])) {
                $pipeline->update(['description' => $validated['description']]);
            }
            if (isset($validated['is_default'])) {
                $pipeline->update(['is_default' => $validated['is_default']]);
            }

            if (isset($validated['stages'])) {
                $existingStageIds = $pipeline->stages()->pluck('id')->toArray();
                $updatedIds = [];

                foreach ($validated['stages'] as $stageData) {
                    if (isset($stageData['id'])) {
                        $stage = PipelineStage::findOrFail($stageData['id']);
                        $stage->update([
                            'name' => $stageData['name'],
                            'probability' => $stageData['probability'],
                            'description' => $stageData['description'] ?? null,
                            'position' => $stageData['position'],
                        ]);
                        $updatedIds[] = $stage->id;
                    } else {
                        $newStage = $pipeline->stages()->create([
                            'name' => $stageData['name'],
                            'probability' => $stageData['probability'],
                            'description' => $stageData['description'] ?? null,
                            'position' => $stageData['position'],
                        ]);
                        $updatedIds[] = $newStage->id;
                    }
                }

                PipelineStage::where('pipeline_id', $pipeline->id)
                    ->whereNotIn('id', $updatedIds)
                    ->delete();
            }
        });

        return response()->json($pipeline->fresh()->load('stages'));
    }

    public function destroy(Pipeline $pipeline): JsonResponse
    {
        $dealCount = Deal::where('pipeline_id', $pipeline->id)->count();
        $activePipelineCount = Pipeline::where('is_active', true)->count();

        if ($dealCount > 0) {
            return response()->json([
                'message' => 'Cannot delete pipeline with deals. Deactivate it instead.',
            ], 422);
        }

        if ($activePipelineCount <= 1 && $pipeline->is_active) {
            return response()->json([
                'message' => 'Cannot delete the last active pipeline.',
            ], 422);
        }

        $pipeline->delete();

        return response()->json(null, 204);
    }

    public function archive(Pipeline $pipeline): JsonResponse
    {
        $activePipelineCount = Pipeline::where('is_active', true)->count();

        if ($activePipelineCount <= 1) {
            return response()->json([
                'message' => 'Cannot deactivate the last active pipeline.',
            ], 422);
        }

        $pipeline->update(['is_active' => false]);

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['pipeline_id' => $pipeline->id])
            ->event('deactivated')
            ->log('Pipeline deactivated');

        return response()->json($pipeline->fresh());
    }

    public function board(Pipeline $pipeline): JsonResponse
    {
        $stages = $pipeline->stages()->withCount('deals')->get();

        $deals = Deal::where('pipeline_id', $pipeline->id)
            ->with(['account', 'contact', 'owner'])
            ->get()
            ->groupBy('stage');

        $columns = $stages->map(function ($stage) use ($deals) {
            $stageDeals = $deals->get($stage->name, collect());
            return [
                'id' => $stage->id,
                'name' => $stage->name,
                'probability' => $stage->probability,
                'deal_count' => $stageDeals->count(),
                'weighted_value' => $stageDeals->sum(fn($d) => $d->getWeightedValue()),
                'total_value' => $stageDeals->sum('value'),
                'deals' => $stageDeals->map(function ($deal) {
                    return [
                        'id' => $deal->id,
                        'title' => $deal->title,
                        'account_name' => $deal->account->name,
                        'value' => $deal->value,
                        'expected_close_date' => $deal->expected_close_date,
                        'owner' => $deal->owner?->only(['id', 'name']),
                    ];
                }),
            ];
        });

        return response()->json(['pipeline' => $pipeline, 'columns' => $columns]);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pipeline;
use App\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PipelineController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:manager|admin');
    }

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

        \Illuminate\Support\Facades\DB::transaction(function () use (&$pipeline, $validated) {
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
        });

        return response()->json($pipeline->load('stages'), 201);
    }

    public function update(Request $request, Pipeline $pipeline): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'sometimes|boolean',
            'stages' => 'sometimes|array',
            'stages.*.id' => 'nullable|string|exists:pipeline_stages,id',
            'stages.*.name' => 'required|string',
            'stages.*.probability' => 'required|integer|min:0|max:100',
            'stages.*.description' => 'nullable|string',
            'stages.*.position' => 'required|integer|min:1',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($pipeline, $validated) {
            if (isset($validated['is_default']) && $validated['is_default']) {
                Pipeline::where('is_default', true)->where('id', '!=', $pipeline->id)->update(['is_default' => false]);
            }

            $pipeline->update([
                'name' => $validated['name'] ?? $pipeline->name,
                'description' => $validated['description'] ?? $pipeline->description,
                'is_default' => $validated['is_default'] ?? $pipeline->is_default,
            ]);

            if (isset($validated['stages'])) {
                $updatedIds = [];
                foreach ($validated['stages'] as $stageData) {
                    if (isset($stageData['id'])) {
                        $stage = $pipeline->stages()->findOrFail($stageData['id']);
                        $stage->update([
                            'name' => $stageData['name'],
                            'probability' => $stageData['probability'],
                            'description' => $stageData['description'] ?? null,
                            'position' => $stageData['position'],
                        ]);
                        $updatedIds[] = $stage->id;
                    } else {
                        $stage = $pipeline->stages()->create([
                            'name' => $stageData['name'],
                            'probability' => $stageData['probability'],
                            'description' => $stageData['description'] ?? null,
                            'position' => $stageData['position'],
                        ]);
                        $updatedIds[] = $stage->id;
                    }
                }

                $pipeline->stages()->whereNotIn('id', $updatedIds)->delete();
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
        return response()->json($pipeline->fresh());
    }
}
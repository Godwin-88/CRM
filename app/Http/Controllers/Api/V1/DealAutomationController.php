<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DealAutomation;
use App\Models\PipelineStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealAutomationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DealAutomation::query()->with(['stage.pipeline']);

        if ($request->filled('pipeline_stage_id')) {
            $query->where('pipeline_stage_id', $request->pipeline_stage_id);
        }

        return response()->json($query->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'actions' => 'nullable|array',
            'actions.*.type' => 'required|in:activity,email,webhook',
            'actions.*.config' => 'nullable|array',
            'actions.*.delay' => 'nullable|in:immediately,1h,1d,3d,5d',
            'actions.*.position' => 'nullable|integer',
        ]);

        $automation = null;

        \DB::transaction(function () use ($validated, &$automation) {
            $automation = DealAutomation::create([
                'pipeline_stage_id' => $validated['pipeline_stage_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['actions'] ?? [] as $index => $action) {
                $automation->actions()->create([
                    'type' => $action['type'],
                    'config' => $action['config'] ?? [],
                    'delay' => $action['delay'] ?? 'immediately',
                    'position' => $action['position'] ?? $index,
                ]);
            }
        });

        return response()->json($automation->load('actions'), 201);
    }

    public function show(DealAutomation $dealAutomation): JsonResponse
    {
        return response()->json($dealAutomation->load('stage.pipeline', 'actions'));
    }

    public function update(Request $request, DealAutomation $dealAutomation): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $dealAutomation->update($validated);

        return response()->json($dealAutomation->fresh()->load('actions'));
    }

    public function destroy(DealAutomation $dealAutomation): JsonResponse
    {
        $dealAutomation->delete();

        return response()->json(null, 204);
    }
}

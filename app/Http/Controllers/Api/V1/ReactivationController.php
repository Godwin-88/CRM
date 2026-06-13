<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ReactivationConfig;
use App\Models\ReactivationContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReactivationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ReactivationConfig::class);

        $query = ReactivationConfig::query()->with('dripSequence', 'creator');

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ReactivationConfig::class);

        $validated = $request->validate([
            'contact_type' => 'required|in:lead,prospect,customer,partner,all',
            'inactivity_days_threshold' => 'required|integer|min:1',
            'drip_sequence_id' => 'nullable|exists:drip_sequences,id',
            'dormant_tag' => 'nullable|string|max:100',
        ]);

        $validated['created_by'] = auth()->id();

        $config = ReactivationConfig::create($validated);

        return response()->json($config->load(['dripSequence', 'creator']), 201);
    }

    public function update(Request $request, ReactivationConfig $config): JsonResponse
    {
        $this->authorize('update', $config);

        $validated = $request->validate([
            'contact_type' => 'sometimes|in:lead,prospect,customer,partner,all',
            'inactivity_days_threshold' => 'sometimes|integer|min:1',
            'drip_sequence_id' => 'nullable|exists:drip_sequences,id',
            'dormant_tag' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $config->update($validated);

        return response()->json($config);
    }

    public function destroy(ReactivationConfig $config): JsonResponse
    {
        $this->authorize('delete', $config);
        $config->delete();

        return response()->json(null, 204);
    }

    public function contacts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ReactivationConfig::class);

        $query = ReactivationContact::query()->with(['contact', 'config', 'dripEnrolment']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('config_id')) {
            $query->where('config_id', $request->config_id);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function analytics(): JsonResponse
    {
        $this->authorize('viewAny', ReactivationConfig::class);

        $contacts = ReactivationContact::with('contact')->get();

        $enrolled = $contacts->where('status', 'enrolled')->count();
        $reEngaged = $contacts->where('status', 're_engaged')->count();
        $completed = $contacts->where('status', 'completed')->count();
        $dormant = $contacts->where('status', 'dormant')->count();

        $reEngagementRate = ($enrolled + $reEngaged + $completed + $dormant) > 0
            ? round(($reEngaged / ($enrolled + $reEngaged + $completed + $dormant)) * 100, 2)
            : 0;

        return response()->json([
            'total_enrolled' => $enrolled,
            'total_re_engaged' => $reEngaged,
            'total_completed' => $completed,
            'total_dormant' => $dormant,
            're_engagement_rate' => $reEngagementRate,
            'by_config' => ReactivationConfig::withCount('contacts')->get()->map(fn ($c) => [
                'contact_type' => $c->contact_type,
                'total' => $c->contacts_count,
            ]),
        ]);
    }
}

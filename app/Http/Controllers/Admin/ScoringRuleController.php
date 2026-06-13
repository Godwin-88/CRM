<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScoringRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScoringRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage scoring rules');
    }

    public function index(): JsonResponse
    {
        return response()->json(
            ScoringRule::orderBy('created_at')->paginate(50)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_type' => 'required|in:contact,account',
            'field' => 'required|string|max:100',
            'operator' => 'required|in:=,!=,>,>=,<,<=,contains,in,between',
            'value' => 'required|string',
            'points' => 'required|integer',
            'is_enabled' => 'boolean',
        ]);

        $rule = ScoringRule::create($validated);

        return response()->json($rule, 201);
    }

    public function update(Request $request, ScoringRule $scoringRule): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'entity_type' => 'sometimes|in:contact,account',
            'field' => 'sometimes|string|max:100',
            'operator' => 'sometimes|in:=,!=,>,>=,<,<=,contains,in,between',
            'value' => 'sometimes|string',
            'points' => 'sometimes|integer',
            'is_enabled' => 'boolean',
        ]);

        $scoringRule->update($validated);

        return response()->json($scoringRule);
    }

    public function destroy(ScoringRule $scoringRule): JsonResponse
    {
        $scoringRule->delete();

        return response()->json(null, 204);
    }

    public function toggle(ScoringRule $scoringRule): JsonResponse
    {
        $scoringRule->update(['is_enabled' => ! $scoringRule->is_enabled]);

        return response()->json($scoringRule);
    }
}

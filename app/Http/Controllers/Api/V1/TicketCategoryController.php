<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TicketCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TicketCategory::query()
            ->with(['parent', 'children', 'slaPolicy', 'defaultTeam']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->paginate($request->get('per_page', 50)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:ticket_categories,id',
            'default_priority' => 'sometimes|in:low,medium,high,urgent',
            'default_team_id' => 'nullable|exists:teams,id',
            'sla_policy_id' => 'nullable|exists:sla_definitions,id',
            'is_agent_only' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $category = TicketCategory::create($validated);

        return response()->json($category->load(['parent', 'children', 'slaPolicy', 'defaultTeam']), 201);
    }

    public function show(TicketCategory $ticketCategory): JsonResponse
    {
        return response()->json($ticketCategory->load([
            'parent',
            'children',
            'slaPolicy',
            'defaultTeam',
            'form',
        ]));
    }

    public function update(Request $request, TicketCategory $ticketCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'sometimes|nullable|exists:ticket_categories,id',
            'default_priority' => 'sometimes|in:low,medium,high,urgent',
            'default_team_id' => 'sometimes|nullable|exists:teams,id',
            'sla_policy_id' => 'sometimes|nullable|exists:sla_definitions,id',
            'is_agent_only' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $ticketCategory->update($validated);

        return response()->json($ticketCategory->fresh()->load(['parent', 'children', 'slaPolicy', 'defaultTeam']));
    }

    public function destroy(TicketCategory $ticketCategory): JsonResponse
    {
        $ticketCategory->delete();

        return response()->json(null, 204);
    }
}
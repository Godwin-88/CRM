<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SlaDefinition;
use App\Models\Team;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupportCategoryController extends Controller
{
    public function index()
    {
        $categories = TicketCategory::with(['parent', 'children', 'slaPolicy', 'defaultTeam'])
            ->orderBy('name')
            ->paginate(50);

        $slaPolicies = SlaDefinition::all(['id', 'name']);
        $teams = Team::all(['id', 'name']);

        return Inertia::render('Admin/Support/Categories', [
            'categories' => $categories,
            'sla_policies' => $slaPolicies,
            'teams' => $teams,
        ]);
    }

    public function store(Request $request)
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

        return redirect()->route('admin.support.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function update(Request $request, TicketCategory $ticketCategory)
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

        return redirect()->route('admin.support.categories.index')
            ->with('success', 'Category updated successfully.');
    }
}

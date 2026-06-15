<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Team::class);

        $teams = Team::with(['lead', 'members.user'])
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $teams,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Team::class);

        $request->validate([
            'name' => 'required|string|max:255|unique:teams,name',
            'description' => 'nullable|string',
            'team_lead_id' => 'nullable|exists:users,id',
        ]);

        $team = Team::create($request->only(['name', 'description', 'team_lead_id']));

        return response()->json([
            'data' => $team->load(['lead', 'members.user']),
        ], 201);
    }

    public function show(string $id)
    {
        $team = Team::with(['lead', 'members.user'])
            ->findOrFail($id);

        $this->authorize('view', $team);

        return response()->json([
            'data' => $team,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $team = Team::findOrFail($id);
        $this->authorize('update', $team);

        $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:teams,name,' . $team->id,
            'description' => 'nullable|string',
            'team_lead_id' => 'nullable|exists:users,id',
        ]);

        $team->update($request->only(['name', 'description', 'team_lead_id']));

        return response()->json([
            'data' => $team->load(['lead', 'members.user']),
        ]);
    }

    public function destroy(string $id)
    {
        $team = Team::findOrFail($id);
        $this->authorize('delete', $team);

        $team->update(['is_archived' => true]);

        return response()->json(['deleted' => true]);
    }

    public function addMember(Request $request, string $id)
    {
        $team = Team::findOrFail($id);
        $this->authorize('addMember', $team);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'is_primary' => 'sometimes|boolean',
        ]);

        $existingMember = TeamMember::where('team_id', $team->id)
            ->where('user_id', $request->user_id)
            ->first();

        if ($existingMember) {
            throw ValidationException::withMessages([
                'user_id' => 'User is already a member of this team.',
            ]);
        }

        if ($request->boolean('is_primary')) {
            TeamMember::where('team_id', $team->id)
                ->update(['is_primary' => false]);
        }

        $member = TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $request->user_id,
            'is_primary' => $request->boolean('is_primary'),
        ]);

        return response()->json([
            'data' => $member->load('user'),
        ], 201);
    }

    public function removeMember(Request $request, string $id, string $userId)
    {
        $team = Team::findOrFail($id);
        $this->authorize('removeMember', $team);

        TeamMember::where('team_id', $team->id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['deleted' => true]);
    }

    public function members(string $id)
    {
        $team = Team::findOrFail($id);
        $this->authorize('view', $team);

        return response()->json([
            'data' => $team->members()->with('user')->get(),
        ]);
    }
}
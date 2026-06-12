<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class QueueStatsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('view', \App\Models\QueueStat::class);

        $teamId = $request->user()->team_id ?? null;

        // Calculate live stats
        $stats = $this->computeStats($teamId);

        return response()->json($stats);
    }

    public function history(Request $request): JsonResponse
    {
        $this->authorize('view', \App\Models\QueueStat::class);

        $hours = (int) $request->get('hours', 24);
        $now = now();
        $history = Cache::get("queue:history:{$hours}", []);

        return response()->json(array_values($history));
    }

    private function computeStats(?string $teamId): array
    {
        $interactionQuery = \App\Models\Interaction::query();

        if ($teamId) {
            $agentIds = \App\Models\User::where('team_id', $teamId)->pluck('id');
            $interactionQuery->whereIn('agent_id', $agentIds);
        }

        $openInteractions = $interactionQuery->clone()
            ->whereIn('status', ['waiting', 'active'])
            ->count();

        $byChannel = $interactionQuery->clone()
            ->select('type', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->map(fn($row) => ['channel' => $row->type, 'count' => $row->count])
            ->values()
            ->all();

        $unassignedAvgWait = $interactionQuery->clone()
            ->whereNull('agent_id')
            ->where('created_at', '<', now()->subMinutes(5))
            ->avg(\Illuminate\Support\Facades\DB::raw('extract(epoch from now() - created_at)')) ?? 0;

        $perAgent = \App\Models\User::query()
            ->select('users.id', 'users.name', \Illuminate\Support\Facades\DB::raw('count(interactions.id) as open_count'))
            ->leftJoin('interactions', function ($join) {
                $join->on('users.id', '=', 'interactions.agent_id')
                     ->whereIn('interactions.status', ['waiting', 'active']);
            })
            ->when($teamId, fn($q) => $q->where('users.team_id', $teamId))
            ->groupBy('users.id', 'users.name')
            ->get()
            ->map(fn($row) => [
                'agent_id' => $row->id,
                'agent_name' => $row->name,
                'open_count' => (int) $row->open_count,
            ])
            ->values()
            ->all();

        $slaBreachRisk = $interactionQuery->clone()
            ->whereHas('ticket', function ($q) {
                $q->where('sla_breached_at', '>', now());
            })
            ->count();

        return [
            'total_open' => (int) $openInteractions,
            'by_channel' => $byChannel,
            'avg_unassigned_wait_seconds' => round($unassignedAvgWait, 1),
            'per_agent' => $perAgent,
            'sla_breach_risk' => (int) $slaBreachRisk,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}

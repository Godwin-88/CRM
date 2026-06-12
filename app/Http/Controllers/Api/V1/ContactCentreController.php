<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Interaction;
use App\Models\UnmatchedItem;
use App\Models\QueueStat;

class ContactCentreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('view', QueueStat::class);

        $user = $request->user();
        $teamId = $user->team_id ?? null;

        $cacheKey = "queue:stats:" . ($teamId ?? 'all');
        $stats = Cache::remember($cacheKey, 10, function () use ($teamId) {
            return $this->computeStats($teamId);
        });

        return response()->json($stats);
    }

    public function history(Request $request): JsonResponse
    {
        $this->authorize('view', QueueStat::class);

        $hours = (int) $request->get('hours', 7 * 24);
        $key = "queue:history:{$hours}";
        $history = Cache::remember($key, 3600, function () use ($hours) {
            return QueueStat::where('recorded_at', '>=', now()->subHours($hours))
                ->orderBy('recorded_at')
                ->get()
                ->map(fn($s) => [
                    'recorded_at' => $s->recorded_at,
                    'total_open' => $s->total_open,
                    'by_channel' => json_decode($s->by_channel, true),
                    'avg_wait_seconds' => $s->avg_wait_seconds,
                    'per_agent' => json_decode($s->per_agent, true),
                ])
                ->values()
                ->all();
        });

        return response()->json($history);
    }

    public function reassign(Request $request, Interaction $interaction): JsonResponse
    {
        $this->authorize('update', Interaction::class);

        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id',
        ]);

        $interaction->update(['agent_id' => $validated['agent_id']]);

        activity()
            ->performedOn($interaction->contact)
            ->causedBy($request->user())
            ->withProperties(['new_agent_id' => $validated['agent_id']])
            ->event('reassigned')
            ->log('Interaction reassigned in queue dashboard');

        return response()->json(['message' => 'Interaction reassigned.']);
    }

    private function computeStats(?string $teamId): array
    {
        $query = Interaction::query();

        if ($teamId) {
            $agentIds = \App\Models\User::where('team_id', $teamId)->pluck('id');
            $query->whereIn('agent_id', $agentIds);
        }

        $totalOpen = (clone $query)->whereIn('status', ['waiting', 'active'])->count();

        $byChannel = (clone $query)
            ->select('type', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->map(fn($row) => ['channel' => $row->type, 'count' => (int) $row->count])
            ->values()
            ->all();

        $avgWait = (clone $query)
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

        $slaBreachRisk = (clone $query)
            ->whereHas('ticket', fn($q) => $q->where('sla_breached_at', '>', now()))
            ->count();

        return [
            'total_open' => (int) $totalOpen,
            'by_channel' => $byChannel,
            'avg_unassigned_wait_seconds' => round((float) $avgWait, 1),
            'per_agent' => $perAgent,
            'sla_breach_risk' => (int) $slaBreachRisk,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}

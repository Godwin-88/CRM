<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SlaDefinition;
use App\Models\SlaInstance;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SlaDefinition::query()->with(['tier', 'businessHours']);

        if ($request->filled('loyalty_tier_id')) {
            $query->where('loyalty_tier_id', $request->loyalty_tier_id);
        }
        if ($request->filled('account_type')) {
            $query->where('account_type', $request->account_type);
        }
        if ($request->filled('is_default')) {
            $query->where('is_default', $request->boolean('is_default'));
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', SlaDefinition::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'support_category_id' => 'nullable|exists:ticket_categories,id',
            'loyalty_tier_id' => 'nullable|exists:loyalty_tiers,id',
            'account_type' => 'nullable|string|max:100',
            'first_response_time_business_hours' => 'nullable|integer|min:1',
            'resolution_time_business_hours' => 'nullable|integer|min:1',
            'is_default' => 'sometimes|boolean',
            'business_hours' => 'nullable|array',
        ]);

        $sla = SlaDefinition::create($validated);

        if ($request->filled('business_hours')) {
            foreach ($request->business_hours as $bh) {
                $sla->businessHours()->create($bh);
            }
        }

        return response()->json($sla->load('businessHours'), 201);
    }

    public function show(SlaDefinition $slaDefinition): JsonResponse
    {
        return response()->json($slaDefinition->load('businessHours', 'instances.ticket'));
    }

    public function update(Request $request, SlaDefinition $slaDefinition): JsonResponse
    {
        $this->authorize('update', $slaDefinition);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'support_category_id' => 'nullable|exists:ticket_categories,id',
            'loyalty_tier_id' => 'nullable|exists:loyalty_tiers,id',
            'account_type' => 'nullable|string|max:100',
            'first_response_time_business_hours' => 'nullable|integer|min:1',
            'resolution_time_business_hours' => 'nullable|integer|min:1',
            'is_default' => 'sometimes|boolean',
            'business_hours' => 'nullable|array',
        ]);

        $slaDefinition->update($validated);

        if ($request->filled('business_hours')) {
            $slaDefinition->businessHours()->delete();
            foreach ($request->business_hours as $bh) {
                $slaDefinition->businessHours()->create($bh);
            }
        }

        return response()->json($slaDefinition->load('businessHours'));
    }

    public function destroy(SlaDefinition $slaDefinition): JsonResponse
    {
        $this->authorize('delete', $slaDefinition);
        $slaDefinition->delete();

        return response()->json(null, 204);
    }

    public function ticketSla(Ticket $ticket): JsonResponse
    {
        $instance = SlaInstance::where('ticket_id', $ticket->id)
            ->with(['slaDefinition', 'slaDefinition.businessHours'])
            ->first();

        if (! $instance) {
            return response()->json(['message' => 'No SLA instance found for this ticket.'], 404);
        }

        $timeRemaining = $this->calculateTimeRemaining($instance);

        return response()->json(array_merge($instance->toArray(), ['time_remaining' => $timeRemaining]));
    }

    public function analytics(): JsonResponse
    {
        $instances = SlaInstance::all();

        $total = $instances->count();
        $breached = $instances->where('first_response_breached', true)
            ->union($instances->where('resolution_breached', true))
            ->unique('id')
            ->count();

        $avgFirstResponse = $instances->filter(fn($i) => $i->first_response_met_at && $i->assigned_at)
            ->map(fn($i) => $i->assigned_at->diffInHours($i->first_response_met_at))
            ->avg();

        $avgResolution = $instances->filter(fn($i) => $i->resolution_met_at && $i->assigned_at)
            ->map(fn($i) => $i->assigned_at->diffInHours($i->resolution_met_at))
            ->avg();

        return response()->json([
            'total_instances' => $total,
            'breached_count' => $breached,
            'breach_rate' => $total > 0 ? round(($breached / $total) * 100, 2) : 0,
            'avg_first_response_hours' => round($avgFirstResponse ?? 0, 2),
            'avg_resolution_hours' => round($avgResolution ?? 0, 2),
            'by_definition' => SlaDefinition::withCount('instances')->get()->map(fn($d) => [
                'name' => $d->name,
                'total' => $d->instances_count,
            ]),
        ]);
    }

    private function calculateTimeRemaining($instance): array
    {
        $now = now();

        if ($instance->first_response_met_at) {
            $firstRemaining = 0;
        } elseif ($instance->first_response_deadline) {
            $firstRemaining = max(0, $instance->first_response_deadline->diffInSeconds($now));
        } else {
            $firstRemaining = null;
        }

        if ($instance->resolution_met_at) {
            $resolutionRemaining = 0;
        } elseif ($instance->resolution_deadline) {
            $resolutionRemaining = max(0, $instance->resolution_deadline->diffInSeconds($now));
        } else {
            $resolutionRemaining = null;
        }

        return [
            'first_response_seconds' => $firstRemaining,
            'resolution_seconds' => $resolutionRemaining,
            'first_response_breach_warning' => $firstRemaining !== null && $firstRemaining > 0 && $firstRemaining <= ($instance->slaDefinition->first_response_time_business_hours * 3600 * 0.2),
            'resolution_breach_warning' => $resolutionRemaining !== null && $resolutionRemaining > 0 && $resolutionRemaining <= ($instance->slaDefinition->resolution_time_business_hours * 3600 * 0.2),
        ];
    }
}

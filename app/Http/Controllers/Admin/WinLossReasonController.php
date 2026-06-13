<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WinLossReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WinLossReasonController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:manager|admin');
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'won' => WinLossReason::won()->get(),
            'lost' => WinLossReason::lost()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:won,lost',
            'label' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $reason = WinLossReason::create($validated);

        return response()->json($reason, 201);
    }

    public function update(Request $request, WinLossReason $winLossReason): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:won,lost',
            'label' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $winLossReason->update($validated);

        return response()->json($winLossReason);
    }

    public function destroy(WinLossReason $winLossReason): JsonResponse
    {
        $hasDeals = $winLossReason->deals()->exists();
        $activeCount = WinLossReason::where('type', $winLossReason->type)->where('is_active', true)->count();

        if ($hasDeals) {
            return response()->json([
                'message' => 'Cannot delete reason used in deals. Deactivate it instead.',
            ], 422);
        }

        if ($activeCount <= 1) {
            return response()->json([
                'message' => 'At least one active reason is required per type.',
            ], 422);
        }

        $winLossReason->delete();

        return response()->json(null, 204);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'type' => 'nullable|string|in:call,email,task,meeting',
            'due_at' => 'nullable|date',
            'contact_id' => 'nullable|string',
            'deal_id' => 'nullable|string',
            'account_id' => 'nullable|string',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|string',
            'body' => 'nullable|string',
        ]);

        $activity = Activity::create([
            'subject' => $validated['subject'],
            'type' => $validated['type'] ?? 'task',
            'due_at' => $validated['due_at'] ?? null,
            'contact_id' => $validated['contact_id'] ?? null,
            'deal_id' => $validated['deal_id'] ?? null,
            'account_id' => $validated['account_id'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'assigned_to' => $validated['assigned_to'] ?? Auth::id(),
            'body' => $validated['body'] ?? null,
            'owner_id' => Auth::id(),
            'status' => 'pending',
        ]);

        return response()->json($activity->load(['contact', 'deal', 'account', 'owner']), 201);
    }
}

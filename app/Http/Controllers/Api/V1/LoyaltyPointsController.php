<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\LoyaltyEnrollment;
use App\Models\LoyaltyProgram;
use App\Models\PointsLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyPointsController extends Controller
{
    public function enroll(Request $request, $contactId): JsonResponse
    {
        $this->authorize('update', Contact::class);

        $validated = $request->validate([
            'program_id' => 'required|exists:loyalty_programs,id',
        ]);

        $contact = Contact::findOrFail($contactId);

        $existing = LoyaltyEnrollment::where('contact_id', $contact->id)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Contact already enrolled in an active loyalty program.'], 422);
        }

        $program = LoyaltyProgram::findOrFail($validated['program_id']);
        if (! $program->is_active) {
            return response()->json(['message' => 'Program is not active.'], 422);
        }

        $enrollment = LoyaltyEnrollment::create([
            'program_id' => $program->id,
            'contact_id' => $contact->id,
            'enrolled_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        activity()
            ->performedOn($contact)
            ->causedBy(auth()->user())
            ->withProperties(['program_id' => $program->id])
            ->event('enrolled')
            ->log('Contact enrolled in loyalty program');

        return response()->json($enrollment->load('program'), 201);
    }

    public function unenroll(Request $request, $contactId): JsonResponse
    {
        $this->authorize('update', Contact::class);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $enrollment = LoyaltyEnrollment::where('contact_id', $contactId)
            ->where('is_active', true)
            ->first();

        if (! $enrollment) {
            return response()->json(['message' => 'No active enrollment found.'], 404);
        }

        $enrollment->update([
            'is_active' => false,
            'unenrolled_at' => now()->toDateString(),
        ]);

        activity()
            ->performedOn(Contact::findOrFail($contactId))
            ->causedBy(auth()->user())
            ->withProperties(['reason' => $validated['reason'] ?? null])
            ->event('unenrolled')
            ->log('Contact unenrolled from loyalty program');

        return response()->json(['message' => 'Contact unenrolled successfully.']);
    }

    public function ledger(Request $request, $contactId): JsonResponse
    {
        $this->authorize('view', Contact::findOrFail($contactId));

        $query = PointsLedger::where('contact_id', $contactId)
            ->with(['program', 'enrollment'])
            ->orderByDesc('transaction_date');

        if ($request->filled('sort_dir') && $request->sort_dir === 'asc') {
            $query->orderByAsc('transaction_date');
        }

        return response()->json($query->paginate(25));
    }

    public function balance(Request $request, $contactId): JsonResponse
    {
        $this->authorize('view', Contact::findOrFail($contactId));

        $enrollment = LoyaltyEnrollment::where('contact_id', $contactId)
            ->where('is_active', true)
            ->with('program')
            ->first();

        if (! $enrollment) {
            return response()->json(['message' => 'No active enrollment found.'], 404);
        }

        $latestLedger = PointsLedger::where('enrollment_id', $enrollment->id)
            ->orderByDesc('transaction_date')
            ->first();

        $currentTier = $this->determineTier($enrollment, $latestLedger?->running_balance ?? 0);
        $nextTier = $this->determineNextTier($enrollment, $latestLedger?->running_balance ?? 0);

        return response()->json([
            'enrollment' => $enrollment,
            'balance' => $latestLedger?->running_balance ?? 0,
            'current_tier' => $currentTier,
            'next_tier' => $nextTier,
        ]);
    }

    public function adjust(Request $request, $contactId): JsonResponse
    {
        $this->authorize('loyalty.adjust');

        $validated = $request->validate([
            'program_id' => 'required|exists:loyalty_programs,id',
            'type' => 'required|in:credit,debit',
            'points_amount' => 'required|integer|min:1',
            'description' => 'nullable|string|max:500',
            'reason_note' => 'required|string|max:500',
        ]);

        $enrollment = LoyaltyEnrollment::where('contact_id', $contactId)
            ->where('program_id', $validated['program_id'])
            ->where('is_active', true)
            ->first();

        if (! $enrollment) {
            return response()->json(['message' => 'No active enrollment found for this program.'], 404);
        }

        $latestLedger = PointsLedger::where('enrollment_id', $enrollment->id)
            ->orderByDesc('transaction_date')
            ->first();
        $currentBalance = $latestLedger?->running_balance ?? 0;

        if ($validated['type'] === 'debit' && $currentBalance < $validated['points_amount']) {
            return response()->json(['message' => 'Insufficient points balance.'], 422);
        }

        $newBalance = $validated['type'] === 'credit'
            ? $currentBalance + $validated['points_amount']
            : $currentBalance - $validated['points_amount'];

        $ledgerEntry = PointsLedger::create([
            'enrollment_id' => $enrollment->id,
            'contact_id' => $contactId,
            'program_id' => $validated['program_id'],
            'type' => $validated['type'],
            'points_amount' => $validated['points_amount'],
            'running_balance' => $newBalance,
            'description' => $validated['description'],
            'triggered_by_event' => 'manual_adjustment',
            'transaction_date' => now(),
            'created_by' => auth()->id(),
            'reason_note' => $validated['reason_note'],
        ]);

        activity()
            ->performedOn(Contact::findOrFail($contactId))
            ->causedBy(auth()->user())
            ->withProperties([
                'program_id' => $validated['program_id'],
                'type' => $validated['type'],
                'points_amount' => $validated['points_amount'],
                'reason' => $validated['reason_note'],
            ])
            ->event('loyalty_adjusted')
            ->log('Loyalty points adjusted manually');

        return response()->json($ledgerEntry->load(['program', 'creator']), 201);
    }

    private function determineTier($enrollment, int $balance): ?array
    {
        $tier = $enrollment->program->tiers()
            ->where('min_points_threshold', '<=', $balance)
            ->orderByDesc('min_points_threshold')
            ->first();

        return $tier ? ['id' => $tier->id, 'name' => $tier->name, 'min_points_threshold' => $tier->min_points_threshold] : null;
    }

    private function determineNextTier($enrollment, int $balance): ?array
    {
        $tier = $enrollment->program->tiers()
            ->where('min_points_threshold', '>', $balance)
            ->orderBy('min_points_threshold')
            ->first();

        return $tier ? ['id' => $tier->id, 'name' => $tier->name, 'min_points_threshold' => $tier->min_points_threshold] : null;
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTier;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyRedemptionRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoyaltyProgramController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(LoyaltyProgram::class, 'program');
    }

    public function index(Request $request): JsonResponse
    {
        $query = LoyaltyProgram::query()->with(['tiers', 'rules', 'redemptionRules']);

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'currency_label' => 'required|string|max:50',
            'expiry_policy' => 'required|in:never,inactivity_months,fixed_date',
            'expiry_inactivity_months' => 'nullable|integer|min:1',
            'expiry_fixed_date' => 'nullable|date',
            'matching_rules' => 'nullable|array',
        ]);

        $validated['created_by'] = auth()->id();

        $program = LoyaltyProgram::create($validated);
        $program->load(['tiers', 'rules', 'redemptionRules']);

        return response()->json($program, 201);
    }

    public function show(LoyaltyProgram $program): JsonResponse
    {
        $program->load(['tiers', 'rules', 'redemptionRules', 'enrollments']);
        return response()->json($program);
    }

    public function update(Request $request, LoyaltyProgram $program): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'currency_label' => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean',
            'expiry_policy' => 'sometimes|in:never,inactivity_months,fixed_date',
            'expiry_inactivity_months' => 'nullable|integer|min:1',
            'expiry_fixed_date' => 'nullable|date',
            'matching_rules' => 'nullable|array',
        ]);

        $program->update($validated);
        $program->load(['tiers', 'rules', 'redemptionRules']);

        return response()->json($program);
    }

    public function destroy(LoyaltyProgram $program): JsonResponse
    {
        $program->delete();
        return response()->json(null, 204);
    }

    // Tiers CRUD nested under program
    public function storeTier(Request $request, LoyaltyProgram $program): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_points_threshold' => 'required|integer|min:0',
            'benefits' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $tier = $program->tiers()->create($validated);

        return response()->json($tier, 201);
    }

    public function updateTier(Request $request, LoyaltyProgram $program, LoyaltyTier $tier): JsonResponse
    {
        if ($tier->program_id !== $program->id) {
            return response()->json(['message' => 'Tier does not belong to this program.'], 422);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'min_points_threshold' => 'sometimes|integer|min:0',
            'benefits' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $tier->update($validated);

        return response()->json($tier);
    }

    public function destroyTier(LoyaltyProgram $program, LoyaltyTier $tier): JsonResponse
    {
        if ($tier->program_id !== $program->id) {
            return response()->json(['message' => 'Tier does not belong to this program.'], 422);
        }

        $tier->delete();

        return response()->json(null, 204);
    }

    // Rules CRUD nested under program
    public function storeRule(Request $request, LoyaltyProgram $program): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:points_per_currency,points_per_interaction,points_for_profile_completion,bonus_points_event',
            'name' => 'required|string|max:255',
            'config' => 'nullable|array',
            'points_amount' => 'required|integer|min:0',
            'multiplier' => 'required|numeric|min:0.01',
            'is_active' => 'sometimes|boolean',
        ]);

        $rule = $program->rules()->create($validated);

        return response()->json($rule, 201);
    }

    public function updateRule(Request $request, LoyaltyProgram $program, LoyaltyRule $rule): JsonResponse
    {
        if ($rule->program_id !== $program->id) {
            return response()->json(['message' => 'Rule does not belong to this program.'], 422);
        }

        $validated = $request->validate([
            'type' => 'sometimes|in:points_per_currency,points_per_interaction,points_for_profile_completion,bonus_points_event',
            'name' => 'sometimes|string|max:255',
            'config' => 'nullable|array',
            'points_amount' => 'sometimes|integer|min:0',
            'multiplier' => 'sometimes|numeric|min:0.01',
            'is_active' => 'sometimes|boolean',
        ]);

        $rule->update($validated);

        return response()->json($rule);
    }

    public function destroyRule(LoyaltyProgram $program, LoyaltyRule $rule): JsonResponse
    {
        if ($rule->program_id !== $program->id) {
            return response()->json(['message' => 'Rule does not belong to this program.'], 422);
        }

        $rule->delete();

        return response()->json(null, 204);
    }

    // Redemption Rules CRUD nested
    public function storeRedemptionRule(Request $request, LoyaltyProgram $program): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:discount_voucher,free_product,tier_upgrade_credit',
            'name' => 'required|string|max:255',
            'config' => 'nullable|array',
            'min_points_threshold' => 'required|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        $rule = $program->redemptionRules()->create($validated);

        return response()->json($rule, 201);
    }

    public function updateRedemptionRule(Request $request, LoyaltyProgram $program, LoyaltyRedemptionRule $rule): JsonResponse
    {
        if ($rule->program_id !== $program->id) {
            return response()->json(['message' => 'Redemption rule does not belong to this program.'], 422);
        }

        $validated = $request->validate([
            'type' => 'sometimes|in:discount_voucher,free_product,tier_upgrade_credit',
            'name' => 'sometimes|string|max:255',
            'config' => 'nullable|array',
            'min_points_threshold' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        $rule->update($validated);

        return response()->json($rule);
    }

    public function destroyRedemptionRule(LoyaltyProgram $program, LoyaltyRedemptionRule $rule): JsonResponse
    {
        if ($rule->program_id !== $program->id) {
            return response()->json(['message' => 'Redemption rule does not belong to this program.'], 422);
        }

        $rule->delete();

        return response()->json(null, 204);
    }
}

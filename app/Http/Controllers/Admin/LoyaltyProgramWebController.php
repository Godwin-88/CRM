<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyEnrollment;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyRedemptionRule;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyTier;
use App\Models\PointsLedger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoyaltyProgramWebController extends Controller
{
    public function index(): Response
    {
        $programs = LoyaltyProgram::with(['tiers', 'rules'])->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/Loyalty', [
            'programs' => $programs,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'program_type' => 'required|string|in:points_based,cashback,tiered',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'currency_label' => 'required|string|max:50',
            'currency_symbol' => 'required|string|max:10',
            'earn_rate' => 'required|numeric|min:0',
            'min_redemption_threshold' => 'required|integer|min:0',
            'expiry_policy' => 'required|string|in:never,inactivity_months,fixed_date',
            'expiry_inactivity_months' => 'nullable|required_if:expiry_policy,inactivity_months|integer|min:1',
            'expiry_fixed_date' => 'nullable|required_if:expiry_policy,fixed_date|date',
        ]);

        $validated['created_by'] = auth()->id();

        LoyaltyProgram::create($validated);

        return redirect()->route('admin.loyalty.index')->with('success', 'Loyalty program created successfully.');
    }

    public function update(Request $request, LoyaltyProgram $loyaltyProgram)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'program_type' => 'required|string|in:points_based,cashback,tiered',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'currency_label' => 'required|string|max:50',
            'currency_symbol' => 'required|string|max:10',
            'earn_rate' => 'required|numeric|min:0',
            'min_redemption_threshold' => 'required|integer|min:0',
            'expiry_policy' => 'required|string|in:never,inactivity_months,fixed_date',
            'expiry_inactivity_months' => 'nullable|required_if:expiry_policy,inactivity_months|integer|min:1',
            'expiry_fixed_date' => 'nullable|required_if:expiry_policy,fixed_date|date',
        ]);

        $loyaltyProgram->update($validated);

        return redirect()->route('admin.loyalty.index')->with('success', 'Loyalty program updated successfully.');
    }

    public function tiers(): Response
    {
        $tiers = LoyaltyTier::orderBy('min_points_threshold')->get();

        return Inertia::render('Admin/LoyaltyTiers', [
            'tiers' => $tiers,
        ]);
    }

    public function storeTier(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:loyalty_programs,id',
            'name' => 'required|string|max:255',
            'min_points_threshold' => 'required|integer|min:0',
            'benefits' => 'nullable|array',
        ]);

        LoyaltyTier::create($request->all());

        return redirect()->route('admin.loyalty.index')->with('success', 'Tier created successfully.');
    }

    public function updateTier(Request $request, LoyaltyTier $loyaltyTier)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'min_points_threshold' => 'required|integer|min:0',
            'benefits' => 'nullable|array',
        ]);

        $loyaltyTier->update($request->all());

        return redirect()->route('admin.loyalty.index')->with('success', 'Tier updated successfully.');
    }

    public function rules(): Response
    {
        $rules = LoyaltyRule::orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/LoyaltyRules', [
            'rules' => $rules,
        ]);
    }

    public function storeRule(Request $request)
    {
        $request->validate([
            'event_type' => 'required|string|max:100',
            'points' => 'required|integer|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        LoyaltyRule::create($request->all());

        return redirect()->route('admin.loyalty.rules')->with('success', 'Rule created successfully.');
    }

    public function updateRule(Request $request, LoyaltyRule $loyaltyRule)
    {
        $request->validate([
            'event_type' => 'required|string|max:100',
            'points' => 'required|integer|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $loyaltyRule->update($request->all());

        return redirect()->route('admin.loyalty.rules')->with('success', 'Rule updated successfully.');
    }

    public function redemptionRules(): Response
    {
        $rules = LoyaltyRedemptionRule::orderBy('points_required')->get();

        return Inertia::render('Admin/LoyaltyRedemptionRules', [
            'rules' => $rules,
        ]);
    }

    public function storeRedemptionRule(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'points_required' => 'required|integer|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        LoyaltyRedemptionRule::create($request->all());

        return redirect()->route('admin.loyalty.redemption-rules')->with('success', 'Redemption rule created successfully.');
    }

    public function updateRedemptionRule(Request $request, LoyaltyRedemptionRule $redemptionRule)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'points_required' => 'required|integer|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $redemptionRule->update($request->all());

        return redirect()->route('admin.loyalty.redemption-rules')->with('success', 'Redemption rule updated successfully.');
    }

    public function ledger(): Response
    {
        $ledger = PointsLedger::with(['contact', 'program'])->orderBy('created_at', 'desc')->limit(200)->get();

        return Inertia::render('Admin/LoyaltyLedger', [
            'ledger' => $ledger,
        ]);
    }

    public function enrollments(): Response
    {
        $enrollments = LoyaltyEnrollment::with(['contact', 'program', 'tier'])->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/LoyaltyEnrollments', [
            'enrollments' => $enrollments,
        ]);
    }
}

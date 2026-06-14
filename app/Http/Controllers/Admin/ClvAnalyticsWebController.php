<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClvCalculation;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\LoyaltyEnrollment;
use App\Models\PointsLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClvAnalyticsWebController extends Controller
{
    public function index(): Response
    {
        $totalPointsIssued = PointsLedger::where('type', 'credit')->sum('points_amount');
        $totalPointsRedeemed = PointsLedger::where('type', 'debit')->sum('points_amount');
        $redemptionRate = $totalPointsIssued > 0 ? round(($totalPointsRedeemed / $totalPointsIssued) * 100, 2) : 0;
        $totalEnrollments = LoyaltyEnrollment::count();
        $activeEnrollments = LoyaltyEnrollment::where('is_active', true)->count();
        $avgClv = round(Contact::avg('clv_score') ?? 0, 2);

        $topContacts = Contact::orderByDesc('clv_score')->limit(20)->get(['id', 'first_name', 'last_name', 'email', 'clv_score', 'ltv']);

        $stats = [
            'avg_clv' => $avgClv,
            'total_points_issued' => $totalPointsIssued,
            'total_points_redeemed' => $totalPointsRedeemed,
            'redemption_rate' => $redemptionRate,
            'total_enrollments' => $totalEnrollments,
            'active_enrollments' => $activeEnrollments,
            'churn_risk_count' => Contact::where('churn_risk_score', '>', 70)->count(),
        ];

        return Inertia::render('Admin/ClvAnalytics', [
            'stats' => $stats,
            'topContacts' => $topContacts,
        ]);
    }

    public function calculations(): Response
    {
        $calculations = ClvCalculation::with(['contact'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return Inertia::render('Admin/ClvCalculations', [
            'calculations' => $calculations,
        ]);
    }

    public function recalculate(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'nullable|exists:contacts,id',
        ]);

        $query = Contact::query();

        if ($validated['contact_id']) {
            $query->where('id', $validated['contact_id']);
        }

        $contacts = $query->get();

        foreach ($contacts as $contact) {
            $this->calculateContactClv($contact);
        }

        return back()->with('success', 'CLV recalculation completed successfully');
    }

    private function calculateContactClv(Contact $contact): void
    {
        $totalRevenue = $contact->deals()
            ->where('status', 'won')
            ->sum('value') ?? 0;

        $engagementScore = $contact->interactions()->count() * 2;
        $loyaltyBoost = $contact->loyaltyEnrollment?->total_points ?? 0;
        $ticketCount = $contact->tickets()->count();
        $satisfactionBoost = max(0, 5 - $ticketCount);

        $yearsActive = max(1, $contact->created_at->diffInDays(now()) / 365);
        $ltv = ($totalRevenue + ($engagementScore * 10) + ($loyaltyBoost * 0.5)) / $yearsActive;
        $clv = round($ltv * $satisfactionBoost, 2);

        $contact->update([
            'clv_score' => $clv,
            'ltv' => round($ltv, 2),
        ]);

        ClvCalculation::create([
            'contact_id' => $contact->id,
            'clv_score' => $clv,
            'ltv' => round($ltv, 2),
            'total_revenue' => $totalRevenue,
            'engagement_score' => $engagementScore,
            'loyalty_boost' => $loyaltyBoost,
            'satisfaction_boost' => $satisfactionBoost,
            'years_active' => round($yearsActive, 2),
            'calculated_at' => now(),
        ]);
    }

    public function churnRisk(): Response
    {
        $highRisk = Contact::where('churn_risk_score', '>', 70)
            ->orderByDesc('churn_risk_score')
            ->limit(50)
            ->get(['id', 'first_name', 'last_name', 'email', 'churn_risk_score', 'last_activity_at']);

        $mediumRisk = Contact::whereBetween('churn_risk_score', [40, 70])
            ->orderByDesc('churn_risk_score')
            ->limit(50)
            ->get(['id', 'first_name', 'last_name', 'email', 'churn_risk_score', 'last_activity_at']);

        return Inertia::render('Admin/ClvChurnRisk', [
            'highRisk' => $highRisk,
            'mediumRisk' => $mediumRisk,
        ]);
    }

    public function segments(): Response
    {
        $highValue = Contact::where('clv_score', '>', 1000)->count();
        $mediumValue = Contact::whereBetween('clv_score', [500, 1000])->count();
        $lowValue = Contact::where('ltv', '<', 500)->count();
        $totalContacts = Contact::count();

        $segments = [
            ['name' => 'High Value', 'count' => $highValue, 'percentage' => $totalContacts > 0 ? round(($highValue / $totalContacts) * 100, 1) : 0, 'color' => 'bg-emerald-500'],
            ['name' => 'Medium Value', 'count' => $mediumValue, 'percentage' => $totalContacts > 0 ? round(($mediumValue / $totalContacts) * 100, 1) : 0, 'color' => 'bg-blue-500'],
            ['name' => 'Low Value', 'count' => $lowValue, 'percentage' => $totalContacts > 0 ? round(($lowValue / $totalContacts) * 100, 1) : 0, 'color' => 'bg-gray-400'],
        ];

        return Inertia::render('Admin/ClvSegments', [
            'segments' => $segments,
            'totalContacts' => $totalContacts,
        ]);
    }

    public function metrics(): JsonResponse
    {
        $totalRevenue = Deal::where('status', 'won')->sum('value') ?? 0;
        $totalContacts = Contact::count();
        $avgDealSize = Deal::where('status', 'won')->avg('value') ?? 0;
        $winRate = Deal::count() > 0 ? round((Deal::where('status', 'won')->count() / Deal::count()) * 100, 2) : 0;

        $ltvByYear = [];
        for ($i = 5; $i >= 1; $i--) {
            $yearStart = now()->subYears($i)->startOfYear();
            $yearEnd = now()->subYears($i)->endOfYear();
            $ltvByYear[] = [
                'year' => $yearStart->year,
                'avg_ltv' => round(Contact::whereBetween('created_at', [$yearStart, $yearEnd])->avg('ltv') ?? 0, 2),
            ];
        }

        return response()->json([
            'total_revenue' => $totalRevenue,
            'total_contacts' => $totalContacts,
            'avg_clv' => round(Contact::avg('clv_score') ?? 0, 2),
            'avg_deal_size' => round($avgDealSize, 2),
            'win_rate' => $winRate,
            'ltv_by_year' => $ltvByYear,
        ]);
    }
}

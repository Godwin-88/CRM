<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClvCalculation;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClvAnalyticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ClvCalculation::class);

        $query = ClvCalculation::query()->with('contact');

        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->input('contact_id'));
        }
        if ($request->filled('contact_type')) {
            $query->whereHas('contact', fn ($q) => $q->where('type', $request->contact_type));
        }
        if ($request->filled('churn_risk_band')) {
            $query->where('churn_risk_band', $request->churn_risk_band);
        }
        if ($request->filled('owner_id')) {
            $query->whereHas('contact', fn ($q) => $q->where('owner_id', $request->owner_id));
        }
        if ($request->filled('date_from')) {
            $query->where('calculated_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('calculated_at', '<=', $request->date_to);
        }

        $sortField = $request->get('sort', 'historical_clv');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        return response()->json($query->paginate($request->get('per_page', 50)));
    }

    public function show(Request $request, string $contact_id): JsonResponse
    {
        $contact = \App\Models\Contact::findOrFail($contact_id);
        $this->authorize('view', $contact);

        $clv = ClvCalculation::where('contact_id', $contact->id)
            ->with('contact')
            ->firstOrFail();

        return response()->json($clv);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ClvCalculation::class);

        $calculations = ClvCalculation::with('contact')->get();

        // Top 20 by historical CLV
        $top20 = $calculations->sortByDesc('historical_clv')->take(20)->values();

        // Average CLV by contact type
        $avgByType = Contact::query()
            ->select('type')
            ->selectRaw('AVG(clv_score) as avg_clv')
            ->groupBy('type')
            ->get();

        // Average CLV by loyalty tier
        $avgByTier = Contact::query()
            ->select('loyalty_tier')
            ->selectRaw('AVG(clv_score) as avg_clv')
            ->groupBy('loyalty_tier')
            ->get();

        // Distribution histogram (buckets)
        $buckets = [0, 100, 500, 1000, 5000, 10000, 50000, 100000];
        $distribution = [];
        foreach ($buckets as $i => $lower) {
            $upper = $buckets[$i + 1] ?? PHP_INT_MAX;
            $distribution[] = [
                'range' => "{$lower} - ".($upper === PHP_INT_MAX ? '∞' : $upper),
                'count' => $calculations->filter(fn ($c) => $c->historical_clv >= $lower && $c->historical_clv < $upper)->count(),
            ];
        }

        // Cohort retention (first 12 months)
        $cohort = $this->buildCohortTable();

        // At-risk contacts
        $atRisk = $calculations->filter(fn ($c) => $c->churn_risk_band === 'high')->take(50);

        // Last calculation timestamp
        $lastCalc = $calculations->max('calculated_at');

        return response()->json([
            'top_20' => $top20,
            'avg_by_type' => $avgByType,
            'avg_by_tier' => $avgByTier,
            'distribution' => $distribution,
            'cohort_retention' => $cohort,
            'at_risk_contacts' => $atRisk->map(fn ($c) => [
                'contact_id' => $c->contact_id,
                'name' => $c->contact->first_name.' '.$c->contact->last_name,
                'historical_clv' => $c->historical_clv,
                'churn_risk_score' => $c->churn_risk_score,
                'churn_risk_band' => $c->churn_risk_band,
                'last_interaction' => $c->contact->updated_at ?? null,
            ])->values(),
            'last_calculated' => $lastCalc,
            'total_contacts' => $calculations->count(),
            'avg_historical_clv' => round($calculations->avg('historical_clv'), 2),
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $this->authorize('export', ClvCalculation::class);

        $query = ClvCalculation::query()->with('contact');

        if ($request->filled('contact_type')) {
            $query->whereHas('contact', fn ($q) => $q->where('type', $request->contact_type));
        }
        if ($request->filled('churn_risk_band')) {
            $query->where('churn_risk_band', $request->churn_risk_band);
        }
        if ($request->filled('owner_id')) {
            $query->whereHas('contact', fn ($q) => $q->where('owner_id', $request->owner_id));
        }

        $data = $query->get()->map(fn ($c) => [
            'contact_id' => $c->contact_id,
            'name' => $c->contact->first_name.' '.$c->contact->last_name,
            'historical_clv' => $c->historical_clv,
            'predicted_ltv' => $c->predicted_ltv,
            'current_tier' => $c->contact->loyalty_tier,
            'churn_risk_score' => $c->churn_risk_score,
            'last_interaction_date' => $c->contact->updated_at ?? null,
        ]);

        return response()->json([
            'filename' => 'clv_export_'.now()->format('Ymd_His').'.csv',
            'data' => $data,
        ]);
    }

    private function buildCohortTable(): array
    {
        $cohort = [];
        for ($month = 1; $month <= 12; $month++) {
            $contacts = Contact::where('created_at', '<=', now()->subMonths($month))
                ->where('created_at', '>', now()->subMonths($month + 1))
                ->get();

            $total = $contacts->count();
            $active = 0;

            foreach ($contacts as $contact) {
                $clv = ClvCalculation::where('contact_id', $contact->id)->first();
                if ($clv && $clv->historical_clv > 0) {
                    $active++;
                }
            }

            $cohort[] = [
                'month' => $month,
                'cohort_size' => $total,
                'active_count' => $active,
                'retention_rate' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            ];
        }

        return $cohort;
    }
}

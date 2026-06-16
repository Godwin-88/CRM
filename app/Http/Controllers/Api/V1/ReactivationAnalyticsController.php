<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ReactivationConfig;
use App\Models\ReactivationContact;
use Illuminate\Http\JsonResponse;

class ReactivationAnalyticsController extends Controller
{
    public function analytics(): JsonResponse
    {
        $this->authorize('viewAny', ReactivationConfig::class);

        $contacts = ReactivationContact::with('contact')->get();

        $enrolled = $contacts->where('status', 'enrolled')->count();
        $reEngaged = $contacts->where('status', 're_engaged')->count();
        $completed = $contacts->where('status', 'completed')->count();
        $dormant = $contacts->where('status', 'dormant')->count();

        $total = $enrolled + $reEngaged + $completed + $dormant;
        $reEngagementRate = $total > 0
            ? round(($reEngaged / $total) * 100, 2)
            : 0;

        $analytics = [
            'total_enrolled' => $enrolled,
            'total_re_engaged' => $reEngaged,
            'total_completed' => $completed,
            'total_dormant' => $dormant,
            're_engagement_rate' => $reEngagementRate,
            'by_config' => ReactivationConfig::withCount('contacts')->get()->map(fn ($c) => [
                'contact_type' => $c->contact_type,
                'total' => $c->contacts_count,
            ])->toArray(),
        ];

        $contactsData = ReactivationContact::with(['contact', 'config'])
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get()
            ->map(fn ($rc) => [
                'id' => $rc->id,
                'contact_name' => $rc->contact?->full_name ?? 'Unknown',
                'contact_email' => $rc->contact?->email ?? '—',
                'status' => $rc->status,
                'enrolled_at' => $rc->created_at?->toDateTimeString() ?? now(),
                're_engaged_at' => $rc->re_engaged_at?->toDateTimeString(),
                'config_name' => $rc->config?->name ?? '—',
            ])
            ->toArray();

        return response()->json([
            'analytics' => $analytics,
            'contacts' => $contactsData,
        ]);
    }
}

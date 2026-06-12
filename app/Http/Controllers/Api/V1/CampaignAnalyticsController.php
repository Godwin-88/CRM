<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignAnalyticsController extends Controller
{
    public function performance(Request $request): JsonResponse
    {
        $campaignId = $request->query('campaign_id');
        
        $query = CampaignRecipient::query();
        
        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }
        
        $stats = $query->selectRaw('
            COUNT(*) as total_sent,
            SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status = "opened" THEN 1 ELSE 0 END) as opened,
            SUM(CASE WHEN status = "clicked" THEN 1 ELSE 0 END) as clicked,
            SUM(CASE WHEN status = "bounced" THEN 1 ELSE 0 END) as bounced,
            SUM(CASE WHEN status = "unsubscribed" THEN 1 ELSE 0 END) as unsubscribed
        ')->first();

        $openRate = $stats->total_sent > 0 ? ($stats->opened / $stats->total_sent) * 100 : 0;
        $clickRate = $stats->total_sent > 0 ? ($stats->clicked / $stats->total_sent) * 100 : 0;

        return response()->json([
            'total_sent' => $stats->total_sent,
            'delivered' => $stats->delivered,
            'bounced' => $stats->bounced,
            'opened' => $stats->opened,
            'clicked' => $stats->clicked,
            'unsubscribed' => $stats->unsubscribed,
            'open_rate' => round($openRate, 2),
            'click_rate' => round($clickRate, 2),
        ]);
    }

    public function timeSeries(string $campaign): JsonResponse
    {
        $data = CampaignRecipient::where('campaign_id', $campaign)
            ->selectRaw('
                DATE_FORMAT(opened_at, "%Y-%m-%d %H:00") as bucket,
                COUNT(*) as opens,
                SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicks
            ')
            ->whereNotNull('opened_at')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->limit(48)
            ->get();

        return response()->json($data);
    }

    public function perLink(string $campaign): JsonResponse
    {
        // This would track actual link clicks - simplified for now
        return response()->json([]);
    }

    public function perContact(string $campaign): JsonResponse
    {
        $contacts = CampaignRecipient::where('campaign_id', $campaign)
            ->with('contact')
            ->select('contact_id', 'status')
            ->get();

        return response()->json($contacts);
    }
}

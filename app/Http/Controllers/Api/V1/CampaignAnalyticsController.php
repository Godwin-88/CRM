<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CampaignRecipient;
use App\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $recipients = CampaignRecipient::where('campaign_id', $campaign)
            ->whereNotNull('redirect_url')
            ->get();

        $linkMap = [];
        foreach ($recipients as $recipient) {
            $url = $recipient->redirect_url;
            if (!isset($linkMap[$url])) {
                $linkMap[$url] = ['url' => $url, 'clicks' => 0, 'unique_clicks' => 0, 'clicks_by_contact' => []];
            }
            $linkMap[$url]['clicks']++;
            if (!in_array($recipient->contact_id, $linkMap[$url]['clicks_by_contact'])) {
                $linkMap[$url]['clicks_by_contact'][] = $recipient->contact_id;
                $linkMap[$url]['unique_clicks']++;
            }
        }

        $links = array_values(array_map(function ($item) {
            unset($item['clicks_by_contact']);
            return $item;
        }, $linkMap));

        return response()->json($links);
    }

    public function perContact(string $campaign): JsonResponse
    {
        $contacts = CampaignRecipient::where('campaign_id', $campaign)
            ->with('contact')
            ->select('contact_id', 'status')
            ->get();

        return response()->json($contacts);
    }

    public function revenue(string $campaign): JsonResponse
    {
        $recipients = CampaignRecipient::where('campaign_id', $campaign)
            ->where('clicked_at', '!=', null)
            ->with('contact')
            ->get();

        $windowDays = 30;
        $totalRevenue = 0;
        $conversions = 0;
        $convertedContacts = [];

        foreach ($recipients as $recipient) {
            if (!$recipient->contact_id) continue;

            $deal = Deal::where('contact_id', $recipient->contact_id)
                ->where('stage', 'Closed Won')
                ->where('clicked_at', '>=', $recipient->clicked_at->subDays($windowDays))
                ->first();

            if ($deal && !in_array($recipient->contact_id, $convertedContacts)) {
                $conversions++;
                $totalRevenue += $deal->value ?? 0;
                $convertedContacts[] = $recipient->contact_id;
            }
        }

        return response()->json([
            'conversions' => $conversions,
            'revenue' => round($totalRevenue, 2),
            'attribution_window_days' => $windowDays,
        ]);
    }

    public function crossCampaign(Request $request): JsonResponse
    {
        $query = CampaignRecipient::query();

        if ($request->filled('campaign_ids')) {
            $query->whereIn('campaign_id', $request->campaign_ids);
        }

        if ($request->filled('start_date')) {
            $query->whereHas('campaign', function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->start_date);
            });
        }

        if ($request->filled('end_date')) {
            $query->whereHas('campaign', function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->end_date);
            });
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_sent,
            SUM(CASE WHEN status = "opened" THEN 1 ELSE 0 END) as opened,
            SUM(CASE WHEN status = "clicked" THEN 1 ELSE 0 END) as clicked,
            SUM(CASE WHEN status = "unsubscribed" THEN 1 ELSE 0 END) as unsubscribed
        ')->first();

        return response()->json([
            'total_sent' => $stats->total_sent,
            'opened' => $stats->opened,
            'clicked' => $stats->clicked,
            'unsubscribed' => $stats->unsubscribed,
            'open_rate' => $stats->total_sent > 0 ? round(($stats->opened / $stats->total_sent) * 100, 2) : 0,
            'click_rate' => $stats->total_sent > 0 ? round(($stats->clicked / $stats->total_sent) * 100, 2) : 0,
        ]);
    }
}

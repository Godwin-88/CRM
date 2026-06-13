<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignTemplate;
use App\Models\Segment;
use Inertia\Inertia;
use Inertia\Response;

class CampaignWebController extends Controller
{
    public function index(): Response
    {
        $campaigns = Campaign::with(['segment', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $segments = Segment::orderBy('name')->get();

        $templates = CampaignTemplate::where('is_active', true)->orderBy('name')->limit(100)->get();

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
            'segments' => $segments,
            'templates' => $templates,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Campaigns/Create');
    }

    public function show(string $campaign): Response
    {
        $campaign = Campaign::with(['segment', 'creator', 'steps.emailTemplate', 'abTest', 'recipients.contact'])->findOrFail($campaign);

        return Inertia::render('Campaigns/Show', ['campaign' => $campaign]);
    }

    public function analytics(): Response
    {
        $campaigns = Campaign::orderBy('created_at', 'desc')->limit(100)->get(['id', 'name', 'status']);

        return Inertia::render('Admin/CampaignAnalytics', [
            'campaigns' => $campaigns,
        ]);
    }
}

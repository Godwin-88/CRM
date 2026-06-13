<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignTemplate;
use Inertia\Inertia;
use Inertia\Response;

class CampaignTemplateWebController extends Controller
{
    public function index(): Response
    {
        $templates = CampaignTemplate::orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return Inertia::render('Admin/CampaignTemplates', [
            'templates' => $templates,
        ]);
    }
}

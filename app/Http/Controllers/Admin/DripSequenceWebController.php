<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignTemplate;
use App\Models\Contact;
use App\Models\DripSequence;
use Inertia\Inertia;
use Inertia\Response;

class DripSequenceWebController extends Controller
{
    public function index(): Response
    {
        $sequences = DripSequence::with(['creator'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return Inertia::render('DripSequences/Index', [
            'sequences' => $sequences,
        ]);
    }

    public function show(DripSequence $sequence): Response
    {
        $sequence->load(['creator', 'steps.emailTemplate', 'steps.segment', 'steps.agent']);

        $templates = CampaignTemplate::where('is_active', true)->orderBy('name')->limit(100)->get(['id', 'name']);
        $contacts = Contact::orderBy('first_name')->limit(200)->get(['id', 'first_name', 'last_name']);

        return Inertia::render('DripSequences/Show', [
            'sequence' => $sequence,
            'templates' => $templates,
            'contacts' => $contacts,
        ]);
    }
}

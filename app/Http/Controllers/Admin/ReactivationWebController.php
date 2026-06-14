<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReactivationConfig;
use App\Models\ReactivationContact;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\Segment;
use App\Models\LoyaltyTier;
use App\Models\DripSequence;

class ReactivationWebController extends Controller
{
    public function index(): Response
    {
        $configs = ReactivationConfig::withCount('contacts')->orderBy('created_at', 'desc')->get();
        $segments = Segment::orderBy('name')->get(['id', 'name']);
        $tiers = LoyaltyTier::orderBy('min_points_threshold')->get(['id', 'name']);
        $sequences = DripSequence::where('status', 'active')->get(['id', 'name']);

        return Inertia::render('Admin/Reactivation', [
            'configs' => $configs,
            'segments' => $segments,
            'tiers' => $tiers,
            'sequences' => $sequences,
            'stats' => [
                'queued' => ReactivationContact::where('status', 'enrolled')->count(),
                'sent' => ReactivationContact::where('status', 're_engaged')->count(), // roughly mapping
                'responded' => ReactivationContact::where('status', 're_engaged')->count(),
                'reactivated' => ReactivationContact::where('status', 'completed')->count(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'criteria' => 'required|array',
            'actions' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();

        ReactivationConfig::create($validated);

        return redirect()->route('admin.reactivation.index')->with('success', 'Reactivation configuration created successfully.');
    }

    public function update(Request $request, ReactivationConfig $reactivationConfig)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'criteria' => 'required|array',
            'actions' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $reactivationConfig->update($validated);

        return redirect()->route('admin.reactivation.index')->with('success', 'Reactivation configuration updated successfully.');
    }

    public function contacts(): Response
    {
        $contacts = ReactivationContact::with(['contact', 'config'])->orderBy('created_at', 'desc')->limit(200)->get();

        return Inertia::render('Admin/ReactivationContacts', [
            'contacts' => $contacts,
        ]);
    }
}

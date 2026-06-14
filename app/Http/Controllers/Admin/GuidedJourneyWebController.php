<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ReactivationConfig;
use App\Models\ReactivationContact;
use App\Models\LoyaltyTier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuidedJourneyWebController extends Controller
{
    public function index(): Response
    {
        $configs = ReactivationConfig::withCount('contacts')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Reactivation', [
            'configs' => $configs,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'criteria' => 'required|json',
            'actions' => 'required|json',
            'is_active' => 'boolean',
        ]);

        ReactivationConfig::create($validated);

        return back()->with('success', 'Reactivation campaign created successfully');
    }

    public function update(Request $request, ReactivationConfig $reactivationConfig)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'criteria' => 'required|json',
            'actions' => 'required|json',
            'is_active' => 'boolean',
        ]);

        $reactivationConfig->update($validated);

        return back()->with('success', 'Reactivation campaign updated successfully');
    }

    public function toggleActive(ReactivationConfig $reactivationConfig)
    {
        $reactivationConfig->update(['is_active' => ! $reactivationConfig->is_active]);

        return back()->with('success', 'Campaign status updated');
    }

    public function destroy(ReactivationConfig $reactivationConfig)
    {
        $reactivationConfig->delete();

        return back()->with('success', 'Campaign deleted successfully');
    }

    public function run(ReactivationConfig $reactivationConfig)
    {
        if (! $reactivationConfig->is_active) {
            return back()->with('error', 'Cannot run inactive campaign');
        }

        $criteria = $reactivationConfig->criteria ?? [];
        $contacts = Contact::query();

        if (isset($criteria['min_inactivity_days'])) {
            $contacts->where('last_activity_at', '<=', now()->subDays((int)$criteria['min_inactivity_days']));
        }

        if (!empty($criteria['segment_id'])) {
            $contacts->whereIn('id', function ($query) use ($criteria) {
                $query->select('contact_id')->from('contact_segments')->where('segment_id', $criteria['segment_id']);
            });
        }

        if (!empty($criteria['loyalty_tier_id'])) {
            $tier = \App\Models\LoyaltyTier::find($criteria['loyalty_tier_id']);
            if ($tier) {
                $contacts->where('loyalty_tier', $tier->name);
            }
        }

        $contactIds = $contacts->pluck('contacts.id');

        $addedCount = 0;
        foreach ($contactIds as $contactId) {
            $exists = ReactivationContact::where('config_id', $reactivationConfig->id)
                ->where('contact_id', $contactId)
                ->exists();

            if (! $exists) {
                ReactivationContact::create([
                    'config_id' => $reactivationConfig->id,
                    'contact_id' => $contactId,
                    'status' => 'enrolled',
                ]);
                $addedCount++;
            }
        }

        return back()->with('success', "Campaign executed. {$addedCount} contacts queued for reactivation.");
    }

    public function contacts(Request $request): Response
    {
        $query = ReactivationContact::with(['contact', 'config']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('campaign')) {
            $query->where('config_id', $request->campaign);
        }

        $contacts = $query->orderBy('created_at', 'desc')->paginate(50);

        $campaigns = ReactivationConfig::all(['id', 'name']);

        $stats = [
            'queued' => ReactivationContact::where('status', 'enrolled')->count(),
            'sent' => ReactivationContact::where('status', 're_engaged')->count(),
            'responded' => ReactivationContact::where('status', 're_engaged')->count(),
            'reactivated' => ReactivationContact::where('status', 'completed')->count(),
        ];

        return Inertia::render('Admin/ReactivationContacts', [
            'contacts' => $contacts,
            'campaigns' => $campaigns,
            'stats' => $stats,
            'filters' => $request->only(['status', 'campaign']),
        ]);
    }

    public function stats(): Response
    {
        $campaigns = ReactivationConfig::withCount('contacts')->get();

        $stats = [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->where('is_active', true)->count(),
            'total_contacts_queued' => ReactivationContact::where('status', 'enrolled')->count(),
            'total_contacts_sent' => ReactivationContact::where('status', 're_engaged')->count(),
            'total_contacts_responded' => ReactivationContact::where('status', 're_engaged')->count(),
            'total_reactivated' => ReactivationContact::where('status', 'completed')->count(),
            'campaign_performance' => [],
        ];

        foreach ($campaigns as $campaign) {
            $stats['campaign_performance'][] = [
                'name' => $campaign->name,
                'queued' => $campaign->contacts_count,
                'is_active' => $campaign->is_active,
            ];
        }

        return Inertia::render('Admin/ReactivationStats', [
            'stats' => $stats,
            'campaigns' => $campaigns,
        ]);
    }

    public function campaigns(): Response
    {
        $configs = ReactivationConfig::orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/ReactivationCampaigns', [
            'configs' => $configs,
        ]);
    }
}

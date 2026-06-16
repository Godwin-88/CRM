<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Interaction;
use App\Models\InteractionChannel;
use App\Models\KioskIntegration;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OmniChannelWebController extends Controller
{
    public function dashboard(): Response
    {
        $stats = [
            'open_tickets' => Ticket::whereIn('status', ['open', 'in_progress'])->count(),
            'calls_today' => Interaction::where('type', 'call')->whereDate('created_at', today())->count(),
            'chat_active' => Interaction::where('type', 'chat')->whereDate('created_at', today())->count(),
            'sms_sent_today' => Interaction::where('type', 'sms')->where('direction', 'outbound')->whereDate('created_at', today())->count(),
            'kiosk_integrations' => KioskIntegration::count(),
        ];

        $recentInteractions = Interaction::with(['contact', 'channel'])->orderBy('created_at', 'desc')->limit(50)->get();
        $recentTickets = Ticket::with('contact')->orderBy('created_at', 'desc')->limit(20)->get();

        return Inertia::render('Admin/OmniChannelDashboard', [
            'stats' => $stats,
            'recentInteractions' => $recentInteractions,
            'recentTickets' => $recentTickets,
        ]);
    }

    public function contactCenter(): Response
    {
        return Inertia::render('Admin/ContactCenter');
    }

    public function tickets(): Response
    {
        $tickets = Ticket::with(['contact'])->orderBy('created_at', 'desc')->limit(200)->get();

        return Inertia::render('Admin/Tickets', [
            'tickets' => $tickets,
        ]);
    }

    public function kiosk(): Response
    {
        $integrations = KioskIntegration::with('location')->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/Kiosk', [
            'integrations' => $integrations,
        ]);
    }

    public function emailCompose(): Response
    {
        return Inertia::render('Admin/EmailCompose', [
            'contacts' => [],
            'emailTemplates' => [],
            'deals' => [],
            'tickets' => [],
        ]);
    }

    public function smsCompose(): Response
    {
        return Inertia::render('Admin/SmsCompose', [
            'contacts' => [],
        ]);
    }

    public function callLog(): Response
    {
        return Inertia::render('Admin/CallLog', [
            'contacts' => [],
            'deals' => [],
            'tickets' => [],
        ]);
    }

    public function ivrTranscriptions(): Response
    {
        $interactions = Interaction::where('type', 'ivr')->with('contact')->orderByDesc('created_at')->limit(100)->get();

        return Inertia::render('Admin/IvrTranscriptions', [
            'interactions' => $interactions,
        ]);
    }

    public function fieldChannel(): Response
    {
        return Inertia::render('Admin/FieldChannel', [
            'snapshot' => ['last_sync' => now()->toIso8601String(), 'contacts' => [], 'accounts' => [], 'activities' => []],
            'pendingCount' => 0,
        ]);
    }

    public function chatInbox(): Response
    {
        return Inertia::render('Admin/ChatInbox', [
            'sessions' => [],
        ]);
    }

    public function storeKiosk(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location_id' => 'nullable|exists:accounts,id',
            'api_key' => 'required|string|max:255',
            'config' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        KioskIntegration::create($request->all());

        return redirect()->route('admin.omni.kiosk')->with('success', 'Kiosk integration created successfully.');
    }
}

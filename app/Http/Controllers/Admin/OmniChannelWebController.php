<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Interaction;
use App\Models\Integration;
use App\Models\InteractionChannel;
use App\Models\KioskIntegration;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OmniChannelWebController extends Controller
{
    public function workspace(): Response
    {
        $agentId = auth()->id();
        $teamView = request()->boolean('team_view', false);

        $query = Interaction::with(['contact', 'channel', 'agent'])
            ->orderBy('created_at', 'desc');

        if (!$teamView) {
            $query->where('agent_id', $agentId);
        }

        $interactions = $query->limit(50)->get();
        $channels = InteractionChannel::orderBy('name')->get(['id', 'name', 'display_name']);

        return Inertia::render('Admin/OmniWorkspace', [
            'interactions' => ['data' => $interactions],
            'channels' => $channels,
        ]);
    }

    public function tools(): Response
    {
        return Inertia::render('Admin/OmniTools');
    }

    public function supervisor(): Response
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

        return Inertia::render('Admin/OmniSupervisor', [
            'stats' => $stats,
            'recentInteractions' => $recentInteractions,
            'recentTickets' => $recentTickets,
        ]);
    }

    public function settings(): Response
    {
        $channels = InteractionChannel::orderBy('name')->get();
        $interactions = Interaction::with(['contact', 'channel'])->orderBy('created_at', 'desc')->limit(100)->get();
        $unmatchedItems = \App\Models\UnmatchedItem::orderBy('created_at', 'desc')->limit(50)->get();
        $socialIntegrations = Integration::whereIn('provider', ['x', 'linkedin', 'facebook', 'instagram', 'tiktok', 'whatsapp'])
            ->orderBy('provider')
            ->get();
        $emailIntegrations = Integration::whereIn('provider', ['mailgun', 'postmark', 'imap', 'email_webhook'])
            ->orderBy('provider')
            ->get();
        $smsIntegrations = Integration::whereIn('provider', ['twilio', 'africastalking'])
            ->orderBy('provider')
            ->get();
        $chatIntegration = Integration::where('provider', 'chat')->first();
        $ivrIntegration = Integration::where('provider', 'ivr')->first();
        $fieldIntegration = Integration::where('provider', 'field')->first();

        return Inertia::render('Admin/OmniSettings', [
            'channels' => $channels,
            'interactions' => $interactions,
            'unmatchedItems' => $unmatchedItems,
            'socialIntegrations' => $socialIntegrations,
            'emailIntegrations' => $emailIntegrations,
            'smsIntegrations' => $smsIntegrations,
            'chatIntegration' => $chatIntegration ? [
                'id' => $chatIntegration->id,
                'provider' => $chatIntegration->provider,
                'name' => $chatIntegration->name,
                'connection_status' => $chatIntegration->connection_status,
                'is_active' => $chatIntegration->is_active,
                'config' => $chatIntegration->config,
                'last_active_at' => $chatIntegration->last_active_at,
            ] : null,
            'ivrIntegration' => $ivrIntegration ? [
                'id' => $ivrIntegration->id,
                'provider' => $ivrIntegration->provider,
                'name' => $ivrIntegration->name,
                'connection_status' => $ivrIntegration->connection_status,
                'is_active' => $ivrIntegration->is_active,
                'config' => $ivrIntegration->config,
                'last_active_at' => $ivrIntegration->last_active_at,
            ] : null,
            'fieldIntegration' => $fieldIntegration ? [
                'id' => $fieldIntegration->id,
                'provider' => $fieldIntegration->provider,
                'name' => $fieldIntegration->name,
                'connection_status' => $fieldIntegration->connection_status,
                'is_active' => $fieldIntegration->is_active,
                'config' => $fieldIntegration->config,
                'last_active_at' => $fieldIntegration->last_active_at,
            ] : null,
        ]);
    }

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

    public function saveSocialChannel(Request $request, $provider)
    {
        $allowedProviders = ['x', 'linkedin', 'facebook', 'instagram', 'tiktok', 'whatsapp'];

        if (!in_array($provider, $allowedProviders)) {
            return back()->with('error', 'Invalid social channel provider.');
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'credentials' => 'required|array',
        ]);

        $channelConfig = $validated['credentials'];
        $integration = Integration::where('provider', $provider)->first();
        $existingConfig = $integration?->config ?? [];

        foreach ($channelConfig as $key => $value) {
            if ($value !== '' && $value !== null) {
                $existingConfig[$key] = $value;
            }
        }

        $sensitiveKeys = [
            'api_key', 'api_secret', 'access_token', 'access_token_secret',
            'client_secret', 'app_secret', 'page_access_token', 'webhook_verify_token',
            'auth_token', 'client_id',
        ];

        foreach ($sensitiveKeys as $key) {
            if (isset($existingConfig[$key])) {
                try {
                    decrypt($existingConfig[$key]);
                } catch (\Exception $e) {
                    $existingConfig[$key] = encrypt($existingConfig[$key]);
                }
            }
        }

        Integration::updateOrCreate(
            ['provider' => $provider],
            [
                'name' => $validated['name'] ?? ucfirst($provider),
                'type' => $provider,
                'config' => $existingConfig,
                'connection_status' => 'connected',
                'is_active' => true,
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', ucfirst($provider) . ' channel configured successfully.');
    }

    public function disconnectSocialChannel($provider)
    {
        $allowedProviders = ['x', 'linkedin', 'facebook', 'instagram', 'tiktok', 'whatsapp'];

        if (!in_array($provider, $allowedProviders)) {
            return back()->with('error', 'Invalid social channel provider.');
        }

        $integration = Integration::where('provider', $provider)->firstOrFail();
        $integration->update([
            'connection_status' => 'not_connected',
            'is_active' => false,
        ]);

        return back()->with('success', ucfirst($provider) . ' channel disconnected.');
    }

    public function saveEmailChannel(Request $request, $provider)
    {
        $allowedProviders = ['mailgun', 'postmark', 'imap', 'email_webhook'];

        if (!in_array($provider, $allowedProviders)) {
            return back()->with('error', 'Invalid email provider.');
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'credentials' => 'required|array',
        ]);

        $channelConfig = $validated['credentials'];
        $integration = Integration::where('provider', $provider)->first();
        $existingConfig = $integration?->config ?? [];

        foreach ($channelConfig as $key => $value) {
            if ($value !== '' && $value !== null) {
                $existingConfig[$key] = $value;
            }
        }

        $sensitiveKeys = ['api_key', 'password', 'webhook_signing_key', 'server_token', 'webhook_token', 'auth_token'];

        foreach ($sensitiveKeys as $key) {
            if (isset($existingConfig[$key])) {
                try {
                    decrypt($existingConfig[$key]);
                } catch (\Exception $e) {
                    $existingConfig[$key] = encrypt($existingConfig[$key]);
                }
            }
        }

        Integration::updateOrCreate(
            ['provider' => $provider],
            [
                'name' => $validated['name'] ?? ucfirst($provider),
                'type' => $provider === 'imap' ? 'email_imap' : 'email_webhook',
                'config' => $existingConfig,
                'connection_status' => 'connected',
                'is_active' => true,
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', ucfirst($provider) . ' email configured successfully.');
    }

    public function disconnectEmailChannel($provider)
    {
        $allowedProviders = ['mailgun', 'postmark', 'imap', 'email_webhook'];

        if (!in_array($provider, $allowedProviders)) {
            return back()->with('error', 'Invalid email provider.');
        }

        $integration = Integration::where('provider', $provider)->firstOrFail();
        $integration->update([
            'connection_status' => 'not_connected',
            'is_active' => false,
        ]);

        return back()->with('success', ucfirst($provider) . ' email disconnected.');
    }

    public function saveSmsChannel(Request $request, $provider)
    {
        $allowedProviders = ['twilio', 'africastalking'];

        if (!in_array($provider, $allowedProviders)) {
            return back()->with('error', 'Invalid SMS provider.');
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'credentials' => 'required|array',
        ]);

        $channelConfig = $validated['credentials'];
        $integration = Integration::where('provider', $provider)->first();
        $existingConfig = $integration?->config ?? [];

        foreach ($channelConfig as $key => $value) {
            if ($value !== '' && $value !== null) {
                $existingConfig[$key] = $value;
            }
        }

        $sensitiveKeys = ['auth_token', 'api_key', 'account_sid'];

        foreach ($sensitiveKeys as $key) {
            if (isset($existingConfig[$key])) {
                try {
                    decrypt($existingConfig[$key]);
                } catch (\Exception $e) {
                    $existingConfig[$key] = encrypt($existingConfig[$key]);
                }
            }
        }

        Integration::updateOrCreate(
            ['provider' => $provider],
            [
                'name' => $validated['name'] ?? ucfirst($provider),
                'type' => $provider,
                'config' => $existingConfig,
                'connection_status' => 'connected',
                'is_active' => true,
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', ucfirst($provider) . ' SMS configured successfully.');
    }

    public function disconnectSmsChannel($provider)
    {
        $allowedProviders = ['twilio', 'africastalking'];

        if (!in_array($provider, $allowedProviders)) {
            return back()->with('error', 'Invalid SMS provider.');
        }

        $integration = Integration::where('provider', $provider)->firstOrFail();
        $integration->update([
            'connection_status' => 'not_connected',
            'is_active' => false,
        ]);

        return back()->with('success', ucfirst($provider) . ' SMS disconnected.');
    }

    public function saveChatChannel(Request $request)
    {
        $validated = $request->validate([
            'config.reverb_host' => 'nullable|string|max:255',
            'config.reverb_port' => 'nullable|string|max:10',
            'config.reverb_app_key' => 'nullable|string|max:255',
            'config.reverb_app_secret' => 'nullable|string|max:255',
            'config.accept_timeout_seconds' => 'nullable|integer|min:30|max:600',
            'config.auto_queue_after_seconds' => 'nullable|integer|min:30|max:600',
            'config.max_concurrent_per_agent' => 'nullable|integer|min:1|max:10',
            'config.chat_widget_id' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $integration = Integration::where('provider', 'chat')->first();

        Integration::updateOrCreate(
            ['provider' => 'chat'],
            [
                'name' => 'Live Chat',
                'type' => 'chat',
                'config' => $validated['config'] ?? [],
                'connection_status' => 'connected',
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Live Chat configured successfully.');
    }

    public function saveIvrChannel(Request $request)
    {
        $validated = $request->validate([
            'config.ingest_url' => 'nullable|url|max:255',
            'config.hmac_secret' => 'nullable|string|max:255',
            'config.rate_limit_per_minute' => 'nullable|integer|min:10|max:1000',
            'config.consecutive_failure_alert_threshold' => 'nullable|integer|min:1|max:50',
            'is_active' => 'boolean',
        ]);

        $integration = Integration::where('provider', 'ivr')->first();

        Integration::updateOrCreate(
            ['provider' => 'ivr'],
            [
                'name' => 'IVR',
                'type' => 'ivr',
                'config' => $validated['config'] ?? [],
                'connection_status' => 'connected',
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'IVR configured successfully.');
    }

    public function saveFieldChannel(Request $request)
    {
        $validated = $request->validate([
            'config.sync_interval_minutes' => 'nullable|integer|min:5|max:1440',
            'config.token_expiry_days' => 'nullable|integer|min:1|max:90',
            'config.supported_fields' => 'nullable|array',
            'config.excluded_fields' => 'nullable|array',
            'config.api_rate_limit' => 'nullable|integer|min:10|max:5000',
            'is_active' => 'boolean',
        ]);

        $integration = Integration::where('provider', 'field')->first();

        Integration::updateOrCreate(
            ['provider' => 'field'],
            [
                'name' => 'Field Channel',
                'type' => 'field',
                'config' => $validated['config'] ?? [],
                'connection_status' => 'connected',
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Field Channel configured successfully.');
    }

    public function disconnectChannel($provider)
    {
        $allowedProviders = ['chat', 'ivr', 'field'];

        if (!in_array($provider, $allowedProviders)) {
            return back()->with('error', 'Invalid channel provider.');
        }

        $integration = Integration::where('provider', $provider)->firstOrFail();
        $integration->update([
            'connection_status' => 'not_connected',
            'is_active' => false,
        ]);

        return back()->with('success', ucfirst($provider) . ' disconnected.');
    }
}

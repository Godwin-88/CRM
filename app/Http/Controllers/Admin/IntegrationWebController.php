<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use Inertia\Inertia;

class IntegrationWebController extends Controller
{
    public function index()
    {
        $integrations = Integration::orderBy('name')->get();

        return Inertia::render('Admin/Integrations/Index', [
            'integrations' => $integrations,
        ]);
    }

    public function marketplace()
    {
        $catalog = [
            ['name' => 'Mailchimp', 'category' => 'communications', 'provider' => 'mailchimp', 'description' => 'Email marketing', 'logo' => null],
            ['name' => 'Slack', 'category' => 'productivity', 'provider' => 'slack', 'description' => 'Team collaboration', 'logo' => null],
            ['name' => 'QuickBooks', 'category' => 'finance', 'provider' => 'quickbooks', 'description' => 'Accounting software', 'logo' => null],
            ['name' => 'Xero', 'category' => 'finance', 'provider' => 'xero', 'description' => 'Accounting software', 'logo' => null],
            ['name' => 'Salesforce', 'category' => 'productivity', 'provider' => 'salesforce', 'description' => 'CRM migration', 'logo' => null],
            ['name' => 'Google Workspace', 'category' => 'productivity', 'provider' => 'google', 'description' => 'SSO & Calendar', 'logo' => null],
            ['name' => 'Azure AD', 'category' => 'identity', 'provider' => 'azure', 'description' => 'SSO & Users', 'logo' => null],
            ['name' => 'DocuSign', 'category' => 'e-signature', 'provider' => 'docusign', 'description' => 'Electronic signatures', 'logo' => null],
            ['name' => 'Twilio', 'category' => 'communications', 'provider' => 'twilio', 'description' => 'SMS & Voice', 'logo' => null],
            ['name' => 'Africa\'s Talking', 'category' => 'communications', 'provider' => 'africastalking', 'description' => 'SMS & Voice (Africa)', 'logo' => null],
            ['name' => 'Stripe', 'category' => 'payments', 'provider' => 'stripe', 'description' => 'Payments', 'logo' => null],
        ];

        return Inertia::render('Admin/Integrations/Marketplace', [
            'catalog' => $catalog,
            'connected' => Integration::pluck('provider')->toArray(),
        ]);
    }

    public function connect(Integration $integration)
    {
        $integration->update([
            'connection_status' => 'connected',
            'last_active_at' => now(),
        ]);

        return back()->with('status', 'Integration connected successfully.');
    }

    public function disconnect(Integration $integration)
    {
        $integration->update([
            'connection_status' => 'not_connected',
            'is_active' => false,
        ]);

        return back()->with('status', 'Integration disconnected.');
    }
}

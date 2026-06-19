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

    public function connect($provider)
    {
        $data = request()->only(['name', 'api_key', 'client_id', 'client_secret']);
        
        $integration = Integration::firstOrCreate(
            ['provider' => $provider],
            [
                'name' => $data['name'] ?? ucfirst($provider),
                'type' => $provider,
                'connection_status' => 'pending',
                'created_by' => auth()->id(),
            ]
        );

        $updateData = [
            'connection_status' => 'connected',
            'last_active_at' => now(),
            'is_active' => true,
        ];

        if (!empty($data['api_key'])) {
            $updateData['api_key'] = encrypt($data['api_key']);
        }
        if (!empty($data['client_id'])) {
            $updateData['config']['client_id'] = $data['client_id'];
        }
        if (!empty($data['client_secret'])) {
            $updateData['config']['client_secret'] = $data['client_secret'];
        }
        if (!empty($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        $integration->update($updateData);

        return back()->with('status', 'Integration connected successfully.');
    }

    public function disconnect($identifier)
    {
        $integration = Integration::where('id', $identifier)
            ->orWhere('provider', $identifier)
            ->firstOrFail();

        $integration->update([
            'connection_status' => 'not_connected',
            'is_active' => false,
        ]);

        return back()->with('status', 'Integration disconnected.');
    }
}

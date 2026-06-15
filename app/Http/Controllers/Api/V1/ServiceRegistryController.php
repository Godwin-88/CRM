<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InboundWebhookLog;
use App\Models\Integration;
use App\Models\IntegrationOAuthClient;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Http\Request;

class ServiceRegistryController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Integration::class);

        $integrations = Integration::select('id', 'name', 'provider', 'category', 'connection_status', 'last_active_at')
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->name,
                'type' => 'connector',
                'status' => $i->connection_status,
                'last_activity_at' => $i->last_active_at,
            ]);

        $webhooks = Webhook::select('id', 'name', 'is_active', 'updated_at as last_activity_at')
            ->get()
            ->map(fn ($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'type' => 'outbound_webhook',
                'status' => $w->is_active ? 'active' : 'paused',
                'last_activity_at' => $w->updated_at,
            ]);

        $oauthClients = IntegrationOAuthClient::select('id', 'name', 'is_suspended', 'updated_at as last_activity_at')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'type' => 'oauth_client',
                'status' => $c->is_suspended ? 'revoked' : 'active',
                'last_activity_at' => $c->updated_at,
            ]);

        $all = $integrations->merge($webhooks)->merge($oauthClients);

        return response()->json([
            'data' => $all->sortByDesc('last_activity_at')->values()->all(),
        ]);
    }

    public function activity(Request $request)
    {
        $activities = collect();

        $recentDeliveries = WebhookDelivery::with('webhook')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($d) => [
                'type' => 'outbound_webhook',
                'integration_id' => $d->webhook_id,
                'event' => $d->event,
                'status' => $d->status,
                'occurred_at' => $d->created_at,
            ]);

        $recentInbound = InboundWebhookLog::latest()->limit(50)->get()
            ->map(fn ($i) => [
                'type' => 'inbound_webhook',
                'provider' => $i->provider,
                'event_id' => $i->event_id,
                'status' => $i->status,
                'occurred_at' => $i->created_at,
            ]);

        return response()->json([
            'data' => $recentDeliveries->merge($recentInbound)->sortByDesc('occurred_at')->values()->all(),
        ]);
    }

    public function export()
    {
        $this->authorize('viewAny', Integration::class);

        $integrations = Integration::select('id', 'name', 'provider', 'category', 'connection_status', 'last_active_at', 'created_at')
            ->get();
        $webhooks = Webhook::select('id', 'name', 'url', 'is_active', 'created_at')->get();
        $oauthClients = IntegrationOAuthClient::select('id', 'name', 'grant_types', 'is_suspended', 'created_at')->get();

        return response()->streamDownload(function () use ($integrations, $webhooks, $oauthClients) {
            echo "id,name,type,status,last_activity\n";
            foreach ($integrations as $i) {
                echo "{$i->id},{$i->name},connector,{$i->connection_status},{$i->last_active_at}\n";
            }
            foreach ($webhooks as $w) {
                echo "{$w->id},{$w->name},outbound_webhook,".($w->is_active ? 'active' : 'paused').",{$w->updated_at}\n";
            }
            foreach ($oauthClients as $c) {
                echo "{$c->id},{$c->name},oauth_client,".($c->is_suspended ? 'revoked' : 'active').",{$c->updated_at}\n";
            }
        }, 'integrations-registry-'.now()->format('Ymd').'.csv');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverWebhook;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WebhookWebController extends Controller
{
    public function index()
    {
        $webhooks = Webhook::with(['creator', 'deliveries' => function ($q) {
            $q->latest()->limit(10);
        }])->latest()->get();

        return Inertia::render('Admin/Integrations/Webhooks/Index', [
            'webhooks' => $webhooks,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'events' => 'required|array|min:1',
        ]);

        $webhook = Webhook::create([
            ...$validated,
            'signing_secret' => 'whsec_'.str()->random(32),
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', 'Webhook created successfully.');
    }

    public function show(Webhook $webhook)
    {
        $webhook->load(['creator', 'deliveries' => function ($q) {
            $q->latest()->paginate(50);
        }]);

        return Inertia::render('Admin/Integrations/Webhooks/Show', [
            'webhook' => $webhook,
        ]);
    }

    public function update(Request $request, Webhook $webhook)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url',
            'events' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $webhook->update($validated);

        return response()->json(['data' => $webhook]);
    }

    public function destroy(Webhook $webhook)
    {
        $webhook->delete();

        return response()->json(['message' => 'Webhook deleted']);
    }

    public function retry($deliveryId)
    {
        $delivery = \App\Models\WebhookDelivery::findOrFail($deliveryId);
        DeliverWebhook::dispatch($delivery->webhook, $delivery->event, $delivery->payload);

        return response()->json(['message' => 'Delivery queued for retry']);
    }
}
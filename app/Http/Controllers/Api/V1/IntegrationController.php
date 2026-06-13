<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Integration::query()->with('creator');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Integration::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:email_imap,email_webhook,twilio,africastalking,ctivendor,kiosk,ivr',
            'provider' => 'required|in:mailgun,postmark,twilio,africastalking,custom',
            'config' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['created_by'] = auth()->id();

        // Encrypt sensitive config values
        if (! empty($validated['config'])) {
            $validated['config'] = $this->encryptConfig($validated['config']);
        }

        $integration = Integration::create($validated);

        return response()->json($integration->load('creator'), 201);
    }

    public function update(Request $request, Integration $integration): JsonResponse
    {
        $this->authorize('update', $integration);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'config' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        if (! empty($validated['config'])) {
            $validated['config'] = $this->encryptConfig($validated['config']);
        }

        $integration->update($validated);

        return response()->json($integration);
    }

    public function destroy(Integration $integration): JsonResponse
    {
        $this->authorize('delete', $integration);
        $integration->delete();

        return response()->json(null, 204);
    }

    public function rotateKey(Request $request, Integration $integration): JsonResponse
    {
        $this->authorize('update', $integration);

        $validated = $request->validate([
            'api_key' => 'required|string',
            'grace_period_hours' => 'nullable|integer|min:0|max:72',
        ]);

        $config = $integration->config ?? [];
        $graceHours = $validated['grace_period_hours'] ?? 24;

        // Store old key in grace period
        $config['old_api_key'] = $config['api_key'] ?? null;
        $config['old_key_expires_at'] = now()->addHours($graceHours)->toIso8601String();
        $config['api_key'] = $validated['api_key'];

        $integration->update(['config' => $config]);

        return response()->json([
            'message' => 'API key rotated. Old key valid for '.$graceHours.' hours.',
            'old_key_expires_at' => $config['old_key_expires_at'],
        ]);
    }

    private function encryptConfig(array $config): array
    {
        foreach (['api_key', 'webhook_secret', 'password', 'auth_token', 'account_sid'] as $key) {
            if (isset($config[$key])) {
                $config[$key] = encrypt($config[$key]);
            }
        }

        return $config;
    }
}

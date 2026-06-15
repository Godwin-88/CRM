<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationOAuthClient;
use Illuminate\Http\Request;
use Laravel\Passport\ClientRepository;
use Inertia\Inertia;

class OAuthClientController extends Controller
{
    public function __construct(protected ClientRepository $clients) {}

    public function index()
    {
        $clients = IntegrationOAuthClient::latest()->get();

        return Inertia::render('Admin/OAuthClients/Index', [
            'clients' => $clients,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'redirect_uris' => 'required|array|min:1',
            'redirect_uris.*' => 'required|url',
            'grant_types' => 'required|array|in:authorization_code,client_credentials',
            'scopes' => 'nullable|array',
        ]);

        $client = $this->clients->create(
            null,
            $validated['name'],
            $validated['redirect_uris'][0],
            null,
            false,
            'http://localhost'
        );

        $oauthClient = IntegrationOAuthClient::create([
            'name' => $validated['name'],
            'redirect_uris' => $validated['redirect_uris'],
            'grant_types' => $validated['grant_types'],
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'scopes' => $validated['scopes'] ?? [],
        ]);

        return response()->json(['data' => $oauthClient], 201);
    }

    public function show(IntegrationOAuthClient $client)
    {
        return response()->json(['data' => $client->load('user')]);
    }

    public function update(Request $request, IntegrationOAuthClient $client)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'redirect_uris' => 'sometimes|array',
            'is_suspended' => 'sometimes|boolean',
        ]);

        $client->update($validated);

        return response()->json(['data' => $client]);
    }

    public function destroy(IntegrationOAuthClient $client)
    {
        $this->clients->delete($client->client_id);
        $client->delete();

        return response()->json(['message' => 'OAuth client deleted']);
    }

    public function suspend(IntegrationOAuthClient $client, Request $request)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $client->update([
            'is_suspended' => true,
            'suspension_reason' => $validated['reason'],
        ]);

        return response()->json(['message' => 'Client suspended']);
    }
}
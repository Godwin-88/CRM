<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IntegrationOAuthClient;
use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Token;

class OAuthController extends Controller
{
    public function authorize(Request $request)
    {
        // This is handled by Passport's OAuth authorization endpoint
        // The view would show the authorization screen
    }

    public function pendingAuthorizations(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'data' => IntegrationOAuthClient::where('user_id', $user->id)
                ->where('is_personal', false)
                ->get(['id', 'name', 'scopes', 'created_at']),
        ]);
    }

    public function revokeAuthorization(Request $request, IntegrationOAuthClient $client)
    {
        $this->authorize('revoke', $client);

        Token::where('client_id', $client->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        $this->logSecurityEvent('oauth_authorization_revoked', $request->user(), $client->name);

        return response()->json(['message' => 'Authorization revoked successfully.']);
    }

    public function suspendClient(Request $request, IntegrationOAuthClient $client)
    {
        $this->authorize('suspend', $client);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($client, $validated) {
            Token::where('client_id', $client->id)->delete();

            $client->update([
                'is_suspended' => true,
                'suspended_at' => now(),
                'suspension_reason' => $validated['reason'],
            ]);
        });

        $this->logSecurityEvent('oauth_client_suspended', $request->user(), $client->name);

        return response()->json(['message' => 'Client suspended and tokens revoked.']);
    }

    private function logSecurityEvent(string $event, $user, ?string $detail = null): void
    {
        SecurityEvent::create([
            'event_type' => $event,
            'user_id' => $user?->id,
            'email' => $user?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'outcome' => 'success',
            'metadata' => ['detail' => $detail],
        ]);
    }
}

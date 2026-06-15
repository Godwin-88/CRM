<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PersonalTokenController extends Controller
{
    public function index()
    {
        $tokens = Auth::user()->tokens()->latest()->get()->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'masked_token' => '••••'.substr($token->token, -4),
                'expires_at' => $token->expires_at,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
            ];
        });

        return Inertia::render('Admin/ApiTokens/Index', [
            'tokens' => $tokens,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);

        $plainToken = $request->user()->createToken(
            $validated['name'],
            $validated['abilities'] ?? [],
            $validated['expires_at']
        )->plainTextToken;

        return back()->with('newToken', $plainToken);
    }

    public function destroy($tokenId)
    {
        $token = Auth::user()->tokens()->findOrFail($tokenId);
        $token->delete();

        return back()->with('message', 'Token revoked successfully');
    }
}

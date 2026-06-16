<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Interaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FieldChannelController extends Controller
{
    public function snapshot(Request $request): JsonResponse
    {
        $user = Auth::user();

        $contacts = Contact::where('account_id', $user->team_id ?? null)
            ->select(['id', 'first_name', 'last_name', 'email', 'phone', 'account_id', 'created_at', 'updated_at'])
            ->get()
            ->map(fn ($c) => $c->only(['id', 'first_name', 'last_name', 'email', 'phone', 'account_id', 'created_at', 'updated_at']));

        $accounts = Account::where('id', $user->team_id ?? 0)
            ->select(['id', 'name', 'industry', 'city', 'country'])
            ->get();

        $activities = DB::table('activities')
            ->where('causer_id', $user->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json([
            'last_sync' => now()->toIso8601String(),
            'contacts' => $contacts,
            'accounts' => $accounts,
            'activities' => $activities,
        ]);
    }

    public function pending(): JsonResponse
    {
        $user = Auth::user();
        $pending = Cache::get("field:pending:{$user->id}", []);

        return response()->json(['pending' => $pending]);
    }

    public function queueFieldVisit(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'account_id' => 'nullable|exists:accounts,id',
            'notes' => 'nullable|string|max:5000',
            'visit_date' => 'required|date',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $pending = Cache::get("field:pending:{$user->id}", []);

        $record = [
            'id' => (string) Str::uuid(),
            'type' => 'field_visit',
            'contact_id' => $validated['contact_id'],
            'account_id' => $validated['account_id'],
            'notes' => $validated['notes'],
            'visit_date' => $validated['visit_date'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'created_at' => now()->toIso8601String(),
        ];

        $pending[] = $record;
        Cache::put("field:pending:{$user->id}", $pending, now()->addDays(30));

        return response()->json(['queued' => $record, 'pending_count' => count($pending)], 201);
    }

    public function sync(Request $request): JsonResponse
    {
        $user = Auth::user();
        $pending = Cache::get("field:pending:{$user->id}", []);

        $synced = [];
        foreach ($pending as $record) {
            if ($record['type'] === 'field_visit') {
                $interaction = Interaction::create([
                    'contact_id' => $record['contact_id'],
                    'account_id' => $record['account_id'],
                    'type' => 'field_visit',
                    'direction' => 'outbound',
                    'subject' => 'Field Visit - '.$record['visit_date'],
                    'body' => $record['notes'] ?? '',
                    'agent_id' => $user->id,
                    'metadata' => [
                        'latitude' => $record['latitude'],
                        'longitude' => $record['longitude'],
                        'visit_date' => $record['visit_date'],
                    ],
                ]);
                $synced[] = $interaction->only(['id', 'type', 'created_at']);
            }
        }

        Cache::forget("field:pending:{$user->id}");

        return response()->json(['synced' => $synced, 'count' => count($synced)]);
    }

    public function rotateToken(Request $request): JsonResponse
    {
        $user = Auth::user();
        $newToken = $user->createToken('field-mobile-'.now()->timestamp)->plainTextToken;

        Cache::put("field:token:{$user->id}", $newToken, now()->addDays(30));

        return response()->json(['token' => $newToken, 'expires_at' => now()->addDays(30)->toIso8601String()]);
    }

    public function revokeToken(Request $request): JsonResponse
    {
        $user = Auth::user();
        $user->tokens()->where('name', 'like', 'field-mobile%')->delete();
        Cache::forget("field:token:{$user->id}");

        return response()->json(['message' => 'Mobile tokens revoked.']);
    }
}

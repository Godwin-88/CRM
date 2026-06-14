<?php

namespace App\Http\Controllers;

use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class PrivilegedSessionController extends Controller
{
    public function showChallenge()
    {
        return inertia('Admin/PrivilegedSessionChallenge');
    }

    public function enter(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
            'mfa_code' => ['nullable', 'string'],
        ]);

        $user = Auth::user();

        if (! Auth::validate(['email' => $user->email, 'password' => $request->password])) {
            throw ValidationException::withMessages([
                'password' => 'Incorrect password.'],
            );
        }

        // If MFA enabled, verify TOTP
        if ($user->mfa_enabled) {
            $code = $request->mfa_code;
            if (! $code) {
                throw ValidationException::withMessages([
                    'mfa_code' => 'MFA code required.'],
                );
            }

            $secret = decrypt($user->mfa_secret_encrypted);
            $valid = (new Google2FA)->verifyKey($secret, $code);

            if (! $valid) {
                throw ValidationException::withMessages([
                    'mfa_code' => 'Invalid MFA code.'],
                );
            }
        }

        // Enter privileged mode for 15 minutes
        $request->session()->put('privileged_until', now()->addMinutes(15));
        $request->session()->put('privileged_started_at', now()->toIso8601String());

        // Log the event
        SecurityEvent::create([
            'event_type' => 'privileged_session_start',
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'outcome' => 'success',
            'metadata' => [
                'mfa_used' => $user->mfa_enabled,
            ],
        ]);

        return back()->with('status', 'Privileged session activated for 15 minutes.');
    }

    public function exit(Request $request)
    {
        $request->session()->forget(['privileged_until', 'is_privileged', 'privileged_started_at']);

        return back()->with('status', 'Exited privileged session mode.');
    }

    private function logSecurityEvent($eventType, $user = null)
    {
        SecurityEvent::create([
            'event_type' => $eventType,
            'user_id' => $user?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'outcome' => 'success',
        ]);
    }
}

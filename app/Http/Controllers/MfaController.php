<?php

namespace App\Http\Controllers;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use PragmaRX\Google2FAQRCode\Google2FA;

class MfaController extends Controller
{
    public function showSetup()
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->mfa_enabled) {
            return redirect()->route('deals.index');
        }

        $qrCode = null;
        $secret = null;

        if ($user->mfa_secret_encrypted) {
            $secret = decrypt($user->mfa_secret_encrypted);
            $qrCode = (new Google2FA)->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $secret
            );
        }

        return Inertia::render('Auth/MfaSetup', [
            'qrCode' => $qrCode,
            'secret' => $secret,
            'requiresMfa' => $this->mfaRequiredForUserRole(),
        ]);
    }

    public function generateSecret(Request $request)
    {
        $user = Auth::user();

        $secret = $user->mfa_secret_encrypted
            ? decrypt($user->mfa_secret_encrypted)
            : app('pragmarx.google2fa.encryption')->encrypt(
                str_replace('-', '', (string) \Str::uuid()),
                null
            );

        // Actually generate a proper secret
        $secret = (new \PragmaRX\Google2FA\Google2FA)->generateSecretKey();
        $request->session()->put('mfa_secret', $secret);

        $qrCode = (new Google2FA)->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return Inertia::render('Auth/MfaSetup', [
            'qrCode' => $qrCode,
            'secret' => $secret,
            'requiresMfa' => $this->mfaRequiredForUserRole(),
        ]);
    }

    public function enable(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $secret = $request->session()->get('mfa_secret');

        if (! $secret) {
            return back()->withErrors(['code' => 'Setup session expired. Please start over.']);
        }

        $valid = (new \PragmaRX\Google2FA\Google2FA)->verifyKey($secret, $request->code);

        if (! $valid) {
            return back()->withErrors(['code' => 'Invalid verification code.']);
        }

        $recoveryCodes = collect(range(1, 10))->map(fn () => 'rc-'.str()->random(8))->toArray();

        $user->update([
            'mfa_secret_encrypted' => encrypt($secret),
            'mfa_recovery_codes_encrypted' => encrypt($recoveryCodes),
            'mfa_enabled' => true,
            'mfa_failed_attempts' => 0,
            'mfa_lockout_until' => null,
        ]);

        $request->session()->forget('mfa_secret');

        return Inertia::render('Auth/MfaSetup', [
            'recoveryCodes' => $recoveryCodes,
            'success' => true,
        ]);
    }

    public function showVerify()
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->mfa_enabled) {
            return redirect()->route('mfa.setup');
        }

        return Inertia::render('Auth/MfaVerify');
    }

    public function verify(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = $request->code;

        if (str_starts_with($code, 'rc-')) {
            return $this->verifyRecoveryCode($request, $user);
        }

        $secret = decrypt($user->mfa_secret_encrypted);
        $valid = (new \PragmaRX\Google2FA\Google2FA)->verifyKey($secret, $code);

        if (! $valid) {
            $this->recordFailedMfaAttempt($user);

            return back()->withErrors(['code' => 'Invalid code.']);
        }

        $user->update([
            'mfa_failed_attempts' => 0,
            'mfa_lockout_until' => null,
        ]);

        $request->session()->put('mfa_verified', true);

        $this->logSecurityEvent('mfa_login_success', $user);

        return redirect()->intended('/deals');
    }

    public function verifyRecoveryCode(Request $request, $user)
    {
        $code = $request->code;
        $recoveryCodes = $user->mfa_recovery_codes;

        if (! in_array($code, $recoveryCodes)) {
            $this->recordFailedMfaAttempt($user);

            return back()->withErrors(['code' => 'Invalid recovery code.']);
        }

        $user->update([
            'mfa_recovery_codes_encrypted' => encrypt(
                array_values(array_filter($recoveryCodes, fn ($rc) => $rc !== $code))
            ),
            'mfa_failed_attempts' => 0,
            'mfa_lockout_until' => null,
        ]);

        $request->session()->put('mfa_verified', true);

        $this->logSecurityEvent('mfa_recovery_used', $user);

        return redirect()->intended('/deals');
    }

    public function disable(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::validate(['email' => $user->email, 'password' => $request->password])) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $user->update([
            'mfa_enabled' => false,
            'mfa_secret_encrypted' => null,
            'mfa_recovery_codes_encrypted' => null,
            'mfa_failed_attempts' => 0,
            'mfa_lockout_until' => null,
        ]);

        $this->logSecurityEvent('mfa_disabled', $user);

        return redirect()->back()->with('status', 'MFA disabled successfully.');
    }

    public function adminReset($userId)
    {
        $this->authorize('security.events');

        $user = User::findOrFail($userId);

        $user->update([
            'mfa_enabled' => false,
            'mfa_secret_encrypted' => null,
            'mfa_recovery_codes_encrypted' => null,
            'mfa_failed_attempts' => 0,
            'mfa_lockout_until' => null,
        ]);

        $this->logSecurityEvent('mfa_admin_reset', $user);

        return back()->with('status', 'MFA reset for user.');
    }

    private function mfaRequiredForUserRole(): bool
    {
        $requiredRoles = config('security.mfa_required_roles', ['admin', 'manager']);

        return collect($requiredRoles)->contains(fn ($role) => Auth::user()->hasRole($role));
    }

    private function recordFailedMfaAttempt($user)
    {
        $attempts = $user->mfa_failed_attempts + 1;
        $lockout = null;

        if ($attempts >= 3) {
            $lockout = now()->addMinutes(5);
        }

        $user->update([
            'mfa_failed_attempts' => $attempts,
            'mfa_lockout_until' => $lockout,
        ]);

        $this->logSecurityEvent('mfa_login_failure', $user);
    }

    private function logSecurityEvent($eventType, $user = null, $dsrType = null, $contactId = null)
    {
        SecurityEvent::create([
            'event_type' => $eventType,
            'user_id' => $user?->id,
            'email' => $user?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'outcome' => 'success',
            'metadata' => $dsrType ? ['dsr_type' => $dsrType, 'contact_id' => $contactId] : null,
        ]);
    }
}

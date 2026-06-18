<?php

namespace App\Http\Controllers;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function loginForm()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Handle lockout check
        if ($user && $user->mfa_lockout_until && now()->isBefore($user->mfa_lockout_until)) {
            $this->logSecurityEvent('login_locked', $user);
            throw ValidationException::withMessages([
                'email' => __('Your account is temporarily locked. Please try again later.'),
            ]);
        }

        // Track failed login attempts for account lockout (5 attempts)
        $loginAttempts = $request->session()->get('login_attempts', 0);
        $request->session()->put('login_attempts', $loginAttempts);

        if (! $user || ! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->logSecurityEvent('login_failure', $user ?? null, $credentials['email']);
            $request->session()->put('login_attempts', $loginAttempts + 1);

            if ($user) {
                $this->checkAndApplyAccountLockout($user, $request->session());
            }

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();
        $request->session()->forget('login_attempts');

        $this->logSecurityEvent('login_success', $user);

        // Check if MFA is required/enabled
        if ($this->mfaRequired($user)) {
            return redirect()->route('mfa.verify');
        }

        Auth::login($user);
        return redirect()->intended(route('admin.analytics.dashboard'));
    }

    public function registerForm()
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'role' => ['required', 'string', 'in:agent,read-only'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $role = Role::where('name', $validated['role'])->first();
        if ($role) {
            $user->assignRole($role);
        }

        $this->logSecurityEvent('register', $user);

        Auth::login($user);

        return redirect()->intended('/deals');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            $this->logSecurityEvent('logout', $user);
        }

        return redirect('/');
    }

    private function mfaRequired($user): bool
    {
        if (! config('security.mfa_enabled', true)) {
            return false;
        }

        $requiredRoles = config('security.mfa_required_roles', ['admin', 'manager']);
        $requiresMfa = collect($requiredRoles)->some(fn ($role) => $user->hasRole($role));

        return $requiresMfa || $user->mfa_enabled;
    }

    private function checkAndApplyAccountLockout($user, $session)
    {
        $attempts = $session->get('login_attempts_by_email', [])[$user->email] ?? 0;
        $session->put("login_attempts_by_email.{$user->email}", $attempts + 1);

        if ($attempts + 1 >= 5) {
            $user->update(['locked_until' => now()->addMinutes(15)]);
            $this->logSecurityEvent('account_locked', $user);
        }
    }

    private function logSecurityEvent($eventType, $user = null, $email = null)
    {
        SecurityEvent::create([
            'event_type' => $eventType,
            'user_id' => $user?->id,
            'email' => $email ?? $user?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'outcome' => 'success',
        ]);
    }
}

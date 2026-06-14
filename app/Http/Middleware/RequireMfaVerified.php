<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMfaVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $this->mfaRequired($user) && ! $request->session()->get('mfa_verified')) {
            return redirect()->route('mfa.verify');
        }

        return $next($request);
    }

    private function mfaRequired($user): bool
    {
        $requiredRoles = config('security.mfa_required_roles', ['admin', 'manager']);
        $requiresMfa = collect($requiredRoles)->some(fn ($role) => $user->hasRole($role));

        return $requiresMfa || $user->mfa_enabled;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrivilegedSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $privilegedUntil = $session->get('privileged_until');

        if ($privilegedUntil && now()->isBefore($privilegedUntil)) {
            $session->put('is_privileged', true);
        } else {
            $session->forget(['privileged_until', 'is_privileged']);
        }

        return $next($request);
    }
}

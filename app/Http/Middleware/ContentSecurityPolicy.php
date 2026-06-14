<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $nonce = base64_encode(random_bytes(20));
        $request->attributes->set('csp_nonce', $nonce);

        $csp = "default-src 'self'; ".
            "script-src 'self' 'nonce-{$nonce}' cdnjs.cloudflare.com; ".
            "style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; ".
            "img-src 'self' data: https:; ".
            "font-src 'self' cdnjs.cloudflare.com; ".
            "frame-src 'self'; ".
            "connect-src 'self';";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}

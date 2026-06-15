<?php

namespace App\Http\Middleware;

use Closure;

class RequestId
{
    public function handle($request, Closure $next): mixed
    {
        $request->attributes->set('request_id', (string) str()->ulid());

        return $next($request);
    }
}

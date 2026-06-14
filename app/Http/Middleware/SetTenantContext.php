<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $tenantId = auth()->user()->account_id;
            if ($tenantId) {
                DB::statement("SET app.current_tenant_id = '{$tenantId}'");
            }
        }

        return $next($request);
    }
}

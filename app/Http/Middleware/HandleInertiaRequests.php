<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'user' => $request->user() ? [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'roles' => $request->user()->roles()->pluck('name')->toArray(),
                'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
                'mfa_enabled' => $request->user()->mfa_enabled,
            ] : null,
            'is_privileged' => $request->session()->get('is_privileged', false),
            'privileged_until' => $request->session()->get('privileged_until'),
            'csrf_token' => csrf_token(),
            'flash' => [
                'newToken' => $request->session()->get('newToken'),
                'message' => $request->session()->get('message'),
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'route_context' => [
                'name' => $request->route()->getName(),
                'path' => $request->path(),
                'params' => $request->route()->parameters(),
            ],
            'assistantEnabled' => true,
        ];
    }
}

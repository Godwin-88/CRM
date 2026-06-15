<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class NotificationWebController extends Controller
{
    public function index($request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(30);

        return Inertia::render('Notifications/Index', [
            'notifications' => [
                'data' => $notifications->items(),
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'total' => $notifications->total(),
                ],
                'links' => [
                    'next' => $notifications->nextPageUrl(),
                    'prev' => $notifications->previousPageUrl(),
                ],
            ],
        ]);
    }

    public function markRead($request, string $id): void
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();
    }

    public function markAllRead($request): void
    {
        $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
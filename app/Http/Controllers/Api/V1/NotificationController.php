<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function unread(Request $request)
    {
        $notifications = $request->user()->unreadNotifications()
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    public function markRead(Request $request, string $id): void
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();
    }

    public function markAllRead(Request $request): void
    {
        $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
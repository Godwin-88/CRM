<?php

namespace App\Http\Controllers;

use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SecurityEventController extends Controller
{
    public function index(Request $request)
    {
        $query = SecurityEvent::query();

        if ($type = $request->get('event_type')) {
            $query->where('event_type', $type);
        }

        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($ip = $request->get('ip_address')) {
            $query->where('ip_address', $ip);
        }

        if ($startDate = $request->get('start_date')) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate = $request->get('end_date')) {
            $query->where('created_at', '<=', $endDate);
        }

        $events = $query->orderBy('created_at', 'desc')
            ->paginate(50)
            ->withQueryString();

        $eventTypes = SecurityEvent::distinct()->pluck('event_type');

        return Inertia::render('Admin/SecurityEvents', [
            'events' => $events,
            'eventTypes' => $eventTypes,
            'filters' => $request->only(['event_type', 'user_id', 'ip_address', 'start_date', 'end_date']),
        ]);
    }
}

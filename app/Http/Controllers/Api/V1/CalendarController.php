<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __construct(
        protected CalendarService $calendarService
    ) {}

    public function index(Request $request)
    {
        $request->validate([
            'view' => 'sometimes|in:month,week,day',
            'start' => 'sometimes|date',
            'end' => 'sometimes|date',
        ]);

        $view = $request->get('view', 'month');
        $start = Carbon::parse($request->get('start', now()->startOfMonth()));
        $end = Carbon::parse($request->get('end', now()->endOfMonth()));

        $teamId = $request->get('team_id');

        $events = $this->calendarService->getEvents(
            $request->user()->id,
            $teamId,
            $view,
            $start,
            $end
        );

        return response()->json([
            'data' => $events,
        ]);
    }
}
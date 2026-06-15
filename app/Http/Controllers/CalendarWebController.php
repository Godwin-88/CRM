<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarWebController extends Controller
{
    public function __construct(
        protected CalendarService $calendarService
    ) {}

    public function index(Request $request)
    {
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

        $teams = Team::active()->get(['id', 'name']);

        return Inertia::render('Calendar/Index', [
            'events' => $events,
            'teams' => $teams,
        ]);
    }
}
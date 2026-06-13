<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SlaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SlaBreachController extends Controller
{
    public function __construct(
        protected SlaService $slaService,
    ) {}

    public function index(Request $request)
    {
        $sortField = $request->get('sort', 'sla_breached_at');
        $sortDir = $request->get('dir', 'desc');

        $breachedTickets = $this->slaService->getBreachedTickets()
            ->orderBy($sortField, $sortDir)
            ->paginate(50);

        return Inertia::render('Admin/Support/SlaBreaches', [
            'tickets' => $breachedTickets,
        ]);
    }
}

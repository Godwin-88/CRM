<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
    ) {}

    public function index(Request $request)
    {
        $tickets = Ticket::query()
            ->with(['contact', 'assignee', 'category', 'slaInstance'])
            ->notMerged()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $categories = TicketCategory::active()->get(['id', 'name']);

        return Inertia::render('Support/Tickets/Index', [
            'tickets' => $tickets,
            'categories' => $categories,
        ]);
    }

    public function show(Ticket $ticket)
    {
        $ticket->load([
            'contact',
            'account',
            'assignee',
            'category.slaPolicy',
            'slaInstance.slaDefinition',
            'internalNotes.author',
            'relatedTickets',
            'rating',
            'linkedArticles',
            'interactions',
        ]);

        return Inertia::render('Support/Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function create()
    {
        $categories = TicketCategory::active()->get();

        return Inertia::render('Support/Tickets/Create', [
            'categories' => $categories,
        ]);
    }
}

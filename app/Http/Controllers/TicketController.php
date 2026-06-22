<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
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
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('subject', 'like', '%'.$request->search.'%')
                        ->orWhereHas('contact', function ($q2) use ($request) {
                            $q2->where('first_name', 'like', '%'.$request->search.'%')
                                ->orWhere('last_name', 'like', '%'.$request->search.'%')
                                ->orWhere('email', 'like', '%'.$request->search.'%');
                        })
                        ->orWhereHas('account', function ($q2) use ($request) {
                            $q2->where('name', 'like', '%'.$request->search.'%');
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->priority))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->category_id))
            ->when($request->filled('assigned_to'), fn ($query) => $query->where('assigned_to', $request->assigned_to))
            ->when($request->filled('account_id'), fn ($query) => $query->where('account_id', $request->account_id))
            ->when($request->filled('contact_id'), fn ($query) => $query->where('contact_id', $request->contact_id))
            ->when($request->filled('sla') && $request->sla === 'breached', function ($query) {
                $query->whereHas('slaInstance', function ($q) {
                    $q->where('first_response_breached', true)
                        ->orWhere('resolution_breached', true)
                        ->orWhereNotNull('first_response_deadline')
                        ->orWhereNotNull('resolution_deadline');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $categories = TicketCategory::active()->get(['id', 'name']);

        return Inertia::render('Support/Tickets/Index', [
            'tickets' => $tickets,
            'categories' => $categories,
            'filters' => $request->only(['search', 'status', 'priority', 'category_id', 'assigned_to', 'account_id', 'contact_id', 'sla']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_id' => 'required|exists:contacts,id',
            'account_id' => 'nullable|exists:accounts,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'category_id' => 'required|exists:ticket_categories,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket = $this->ticketService->createTicket($validated);

        return redirect()->route('support.tickets.show', $ticket)->with('success', 'Ticket created successfully.');
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

    public function create(Request $request)
    {
        $categories = TicketCategory::orderBy('name')->get(['id', 'name']);
        $agents = User::whereNotNull('name')->orderBy('name')->get(['id', 'name']);
        $contacts = \App\Models\Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);
        $accounts = \App\Models\Account::orderBy('name')->get(['id', 'name']);
        $prefill = $request->only(['subject', 'description', 'contact_id', 'account_id', 'priority', 'category_id', 'assigned_to']);

        return Inertia::render('Support/Tickets/Create', [
            'categories' => $categories,
            'agents' => $agents,
            'contacts' => $contacts,
            'accounts' => $accounts,
            'prefill' => $prefill,
        ]);
    }
}

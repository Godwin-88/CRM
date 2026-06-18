<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Jobs\SendCsatRequest;
use App\Models\CannedResponse;
use App\Models\KnowledgeBaseArticle;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\SlaService;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
        protected SlaService $slaService,
    ) {}

    public function index(Request $request)
    {
        $query = Ticket::query()
            ->with(['contact', 'assignee', 'category', 'slaInstance.slaDefinition'])
            ->notMerged();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(20);
        $categories = TicketCategory::active()->get(['id', 'name']);

        return Inertia::render('Support/Tickets/Index', [
            'tickets' => $tickets,
            'categories' => $categories,
            'filters' => $request->only(['status', 'priority', 'category_id']),
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

        $cannedResponses = CannedResponse::active()
            ->orderBy('usage_count', 'desc')
            ->limit(50)
            ->get(['id', 'title', 'body', 'category_tag']);

        return Inertia::render('Support/Tickets/Show', [
            'ticket' => $ticket,
            'cannedResponses' => $cannedResponses,
        ]);
    }

    public function create()
    {
        $categories = TicketCategory::active()->get();
        $agents = User::role('agent')->get(['id', 'name']);

        return Inertia::render('Support/Tickets/Create', [
            'categories' => $categories,
            'agents' => $agents,
        ]);
    }

    public function suggestArticles(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string',
        ]);

        $query = KnowledgeBaseArticle::search($validated['subject'])
            ->published()
            ->take(5)
            ->get();

        return response()->json([
            'articles' => $query->map(fn ($article) => [
                'id' => $article->id,
                'title' => $article->title,
                'excerpt' => \Illuminate\Support\Str::limit(strip_tags($article->body), 150),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_id' => 'required|exists:contacts,id',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'category_id' => 'required|exists:ticket_categories,id',
            'assigned_to' => 'nullable|exists:users,id',
            'form_response' => 'sometimes|array',
        ]);

        $ticket = $this->ticketService->createTicket($validated);
        $this->slaService->assignSlaToTicket($ticket);

        return redirect()->route('support.tickets.show', $ticket)->with('success', 'Ticket created successfully.');
    }

    public function assign(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id',
        ]);

        $agent = User::find($validated['agent_id']);
        $this->ticketService->assignTicket($ticket, $agent, $request->user());

        return back()->with('success', 'Ticket assigned successfully.');
    }

    public function escalate(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'escalation_reason' => 'required|string',
        ]);

        $ticket->escalation_reason = $validated['escalation_reason'];
        $ticket->save();

        $this->ticketService->escalateTicket($ticket, $request->user(), $validated['escalation_reason']);

        return back()->with('success', 'Ticket escalated successfully.');
    }

    public function resolve(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'resolution_note' => 'required|string',
        ]);

        $this->ticketService->resolveTicket($ticket, $validated['resolution_note'], $request->user());

        // Dispatch CSAT job
        SendCsatRequest::dispatch($ticket)->delay(now()->addHour());

        return back()->with('success', 'Ticket resolved successfully.');
    }

    public function close(Ticket $ticket)
    {
        $this->ticketService->closeTicket($ticket);

        return back()->with('success', 'Ticket closed successfully.');
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'subject' => 'sometimes|string',
            'body' => 'required|string',
        ]);

        $ticket->interactions()->create([
            'contact_id' => $ticket->contact_id,
            'account_id' => $ticket->account_id,
            'type' => 'email',
            'direction' => 'outbound',
            'subject' => $validated['subject'] ?? "Re: {$ticket->subject}",
            'body' => $validated['body'],
        ]);

        // Update SLA first response if this is the first outbound reply
        $this->slaService->checkFirstResponseMet($ticket);

        return back()->with('success', 'Reply sent successfully.');
    }

    public function getCategoryForm(TicketCategory $category)
    {
        $form = $category->form;
        return response()->json([
            'form' => $form ? [
                'id' => $form->id,
                'name' => $form->name,
                'fields' => $form->fields ?? [],
            ] : null,
        ]);
    }
}

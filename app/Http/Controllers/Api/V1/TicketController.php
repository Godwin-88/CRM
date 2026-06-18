<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlaService;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
        protected SlaService $slaService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Ticket::query()
            ->with(['contact', 'assignee', 'category', 'slaInstance.slaDefinition'])
            ->notMerged();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhereHas('contact', function ($q2) use ($search) {
                        $q2->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_id' => 'required|exists:contacts,id',
            'account_id' => 'nullable|exists:accounts,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'category_id' => 'nullable|exists:ticket_categories,id',
            'assigned_to' => 'nullable|exists:users,id',
            'form_response' => 'nullable|array',
            'is_agent_created' => 'sometimes|boolean',
        ]);

        $ticket = $this->ticketService->createTicket($validated);

        // Assign SLA
        $this->slaService->assignSlaToTicket($ticket);

        return response()->json($ticket->load(['contact', 'assignee', 'category']), 201);
    }

    public function show(Ticket $ticket): JsonResponse
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

        return response()->json($ticket);
    }

    public function updateStatus(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,waiting_on_customer,resolved,closed',
        ]);

        $this->ticketService->changeStatus($ticket, $validated['status']);

        return response()->json($ticket->fresh()->load(['contact', 'assignee', 'category']));
    }

    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:open,in_progress,waiting_on_customer,resolved,closed',
            'category_id' => 'sometimes|nullable|exists:ticket_categories,id',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
        ]);

        if (isset($validated['status'])) {
            $this->ticketService->changeStatus($ticket, $validated['status']);
        }

        if (isset($validated['assigned_to'])) {
            $this->ticketService->assignTicket($ticket, User::find($validated['assigned_to']), Auth::user());
        }

        $ticket->update(collect($validated)->except(['status', 'assigned_to'])->all());

        return response()->json($ticket->fresh()->load(['contact', 'assignee', 'category']));
    }

    public function destroy(Ticket $ticket): JsonResponse
    {
        $ticket->delete();

        return response()->json(null, 204);
    }

    public function assign(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id',
        ]);

        $agent = User::find($validated['agent_id']);
        $this->ticketService->assignTicket($ticket, $agent, Auth::user());

        return response()->json($ticket->fresh()->load('assignee'));
    }

    public function escalate(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'escalation_reason' => 'required|string',
        ]);

        $this->ticketService->escalateTicket($ticket, Auth::user(), $validated['escalation_reason']);

        return response()->json($ticket->fresh());
    }

    public function resolve(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'resolution_note' => 'required|string',
        ]);

        $this->ticketService->resolveTicket($ticket, $validated['resolution_note'], Auth::user());

        return response()->json($ticket->fresh());
    }

    public function close(Ticket $ticket): JsonResponse
    {
        $this->ticketService->closeTicket($ticket);

        return response()->json($ticket->fresh());
    }

    public function reopen(Ticket $ticket): JsonResponse
    {
        $this->ticketService->reopenTicket($ticket);

        return response()->json($ticket->fresh());
    }

    public function merge(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'target_ticket_id' => 'required|exists:tickets,id|different:'.$ticket->id,
        ]);

        $targetTicket = Ticket::find($validated['target_ticket_id']);
        $this->ticketService->mergeTickets($ticket, $targetTicket);

        return response()->json(['message' => 'Tickets merged successfully.']);
    }

    public function split(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'interaction_ids' => 'required|array',
            'interaction_ids.*' => 'exists:interactions,id',
        ]);

        $newTicket = $this->ticketService->splitTicket($ticket, $validated['interaction_ids'], Auth::user());

        return response()->json($newTicket->load(['contact', 'interactions']));
    }

    public function addNote(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'body' => 'required|string',
            'mentions' => 'sometimes|array',
            'mentions.*' => 'exists:users,id',
        ]);

        $note = $this->ticketService->addInternalNote(
            $ticket,
            $validated['body'],
            Auth::user(),
            $validated['mentions'] ?? []
        );

        return response()->json($note->load('mentions.user'));
    }

    public function linkArticle(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'article_id' => 'required|exists:knowledge_base_articles,id',
        ]);

        $article = KnowledgeBaseArticle::find($validated['article_id']);
        $this->ticketService->linkArticle($ticket, $article);

        return response()->json(['message' => 'Article linked to ticket.']);
    }

    public function breached(): JsonResponse
    {
        return response()->json(
            $this->slaService->getBreachedTickets()->paginate()
        );
    }
}

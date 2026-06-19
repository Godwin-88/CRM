<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Models\CannedResponse;
use App\Models\KnowledgeBaseCategory;
use App\Models\KnowledgeBaseArticle;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketRating;
use App\Services\TicketService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PortalController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
    ) {}

    public function dashboard()
    {
        $contact = auth()->user()?->contact ?? null;

        $tickets = $contact
            ? Ticket::where('contact_id', $contact->id)
                ->where('is_agent_created', false)
                ->with(['rating', 'category', 'slaInstance.slaDefinition'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
            : collect();

        return Inertia::render('SelfService/Dashboard', [
            'tickets' => $tickets,
        ]);
    }

    public function createTicket()
    {
        $contact = auth()->user()?->contact;
        $categories = TicketCategory::active()
            ->where('is_agent_only', false)
            ->get(['id', 'name', 'default_priority', 'is_agent_only']);

        return Inertia::render('SelfService/CreateTicket', [
            'contact' => $contact,
            'categories' => $categories,
        ]);
    }

    public function storeTicket(Request $request)
    {
        $contact = auth()->user()?->contact;

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:ticket_categories,id',
            'form_response' => 'sometimes|array',
        ]);

        $category = TicketCategory::find($validated['category_id']);
        if ($category->is_agent_only) {
            abort(403, 'This category is not available in the self-service portal.');
        }

        $ticket = $this->ticketService->createTicket([
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'contact_id' => $contact->id,
            'priority' => $category->default_priority ?? 'medium',
            'category_id' => $validated['category_id'],
            'form_response' => $validated['form_response'] ?? null,
        ]);

        return redirect()->route('self-service.tickets.show', $ticket)
            ->with('success', 'Ticket created successfully.');
    }

    public function showTicket(Ticket $ticket)
    {
        $contact = auth()->user()?->contact;

        if ($ticket->contact_id !== $contact->id && ! $ticket->is_agent_created) {
            abort(403, 'Access denied.');
        }

        $ticket->load([
            'category',
            'rating',
            'interactions' => function ($q) {
                $q->orderBy('created_at', 'asc');
            },
        ]);

        $cannedResponses = CannedResponse::active()
            ->where('is_active', true)
            ->orderBy('usage_count', 'desc')
            ->limit(50)
            ->get(['id', 'title', 'body', 'category_tag']);

        return Inertia::render('SelfService/TicketShow', [
            'ticket' => $ticket,
            'cannedResponses' => $cannedResponses,
        ]);
    }

    public function rateTicket(Ticket $ticket, int $score)
    {
        $contact = auth()->user()?->contact;

        if ($ticket->contact_id !== $contact->id) {
            abort(403);
        }

        if ($ticket->rating) {
            return redirect()->back()->with('message', 'Thank you! Your rating has already been recorded.');
        }

        $this->ticketService->recordRating($ticket, $score);

        return redirect()->back()->with('success', 'Thank you for your feedback!');
    }

    public function knowledgeBase()
    {
        $articles = KnowledgeBaseArticle::with('category')
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate(20);

        $categories = KnowledgeBaseCategory::with('children')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('SelfService/KnowledgeBase/Index', [
            'articles' => $articles,
            'categories' => $categories,
        ]);
    }

    public function showArticle(KnowledgeBaseArticle $article)
    {
        if ($article->status !== 'published') {
            abort(404);
        }

        $article->increment('view_count');

        return Inertia::render('SelfService/KnowledgeBase/Show', [
            'article' => $article->load('category'),
        ]);
    }

    public function searchArticles(Request $request)
    {
        $query = $request->input('q');

        if (! $query) {
            return response()->json(['articles' => []]);
        }

        $articles = KnowledgeBaseArticle::search($query)
            ->published()
            ->take(10)
            ->get();

        return response()->json([
            'articles' => $articles->map(fn ($article) => [
                'id' => $article->id,
                'title' => $article->title,
                'excerpt' => Str::limit(strip_tags($article->body), 150),
                'category' => $article->category?->name,
            ]),
        ]);
    }
}
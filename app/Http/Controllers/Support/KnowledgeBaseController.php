<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Services\KnowledgeBaseService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KnowledgeBaseController extends Controller
{
    public function __construct(
        protected KnowledgeBaseService $knowledgeBaseService,
    ) {}

    public function index(Request $request)
    {
        $query = KnowledgeBaseArticle::query()
            ->with(['category', 'author'])
            ->published();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                ->orWhere('body', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $articles = $query->orderBy('published_at', 'desc')->paginate(20);
        $categories = \App\Models\KnowledgeBaseCategory::with('children')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Support/KnowledgeBase/Index', [
            'articles' => $articles,
            'categories' => $categories,
        ]);
    }

    public function show(KnowledgeBaseArticle $article)
    {
        $article->incrementViewCount();

        return Inertia::render('Support/KnowledgeBase/Show', [
            'article' => $article->load('category'),
        ]);
    }

    public function rate(Request $request, KnowledgeBaseArticle $article)
    {
        $validated = $request->validate([
            'helpful' => 'required|boolean',
        ]);

        $article->recordHelpfulVote($validated['helpful']);

        return back()->with('success', 'Thank you for your feedback!');
    }

    public function linkToTicket(Request $request, KnowledgeBaseArticle $article)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
        ]);

        $ticket = \App\Models\Ticket::find($validated['ticket_id']);
        $ticket->linkedArticles()->attach($article->id);

        return back()->with('success', 'Article linked to ticket.');
    }
}
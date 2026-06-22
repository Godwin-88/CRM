<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\Ticket;
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
        $user = $request->user();
        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('manager'));

        if ($isAdmin) {
            $query = KnowledgeBaseArticle::query()
                ->with(['category', 'author']);

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%'.$request->search.'%')
                      ->orWhere('body', 'like', '%'.$request->search.'%');
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $articles = $query->orderBy('created_at', 'desc')->paginate(20);
            $categories = KnowledgeBaseCategory::orderBy('sort_order')->get();

            return Inertia::render('Support/KnowledgeBase/Index', [
                'articles' => $articles,
                'categories' => $categories,
                'isAdmin' => true,
            ]);
        }

        $query = KnowledgeBaseArticle::query()
            ->with(['category', 'author'])
            ->published();

        if ($request->filled('search')) {
            $query = KnowledgeBaseArticle::search($request->search)->published();
        } elseif ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $articles = $query->orderBy('published_at', 'desc')->paginate(20);
        $categories = KnowledgeBaseCategory::with('children')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Support/KnowledgeBase/Index', [
            'articles' => $articles,
            'categories' => $categories,
            'isAdmin' => false,
        ]);
    }

    public function create()
    {
        $categories = KnowledgeBaseCategory::orderBy('sort_order')->get();

        return Inertia::render('Support/KnowledgeBase/Editor', [
            'categories' => $categories,
            'article' => null,
        ]);
    }

    public function edit(KnowledgeBaseArticle $article)
    {
        $categories = KnowledgeBaseCategory::orderBy('sort_order')->get();

        return Inertia::render('Support/KnowledgeBase/Editor', [
            'categories' => $categories,
            'article' => $article->load('category'),
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

        $ticket = Ticket::find($validated['ticket_id']);
        $ticket->linkedArticles()->attach($article->id);

        return back()->with('success', 'Article linked to ticket.');
    }
}

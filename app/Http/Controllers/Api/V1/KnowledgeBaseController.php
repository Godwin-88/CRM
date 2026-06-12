<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Services\KnowledgeBaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class KnowledgeBaseController extends Controller
{
    public function __construct(
        protected KnowledgeBaseService $knowledgeBaseService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = KnowledgeBaseArticle::query()
            ->with(['category', 'author']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                ->orWhere('body', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->user()->cannot('viewAnyAsCustomer', KnowledgeBaseArticle::class)) {
            $query->whereIn('status', ['published', 'approved', 'in_review', 'draft']);
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category_id' => 'required|exists:knowledge_base_categories,id',
            'status' => 'sometimes|in:draft,in_review,approved,published,archived',
        ]);

        $article = KnowledgeBaseArticle::create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'body' => $validated['body'],
            'category_id' => $validated['category_id'],
            'author_id' => Auth::id(),
            'status' => $validated['status'] ?? 'draft',
        ]);

        return response()->json($article->load(['category', 'author']), 201);
    }

    public function show(KnowledgeBaseArticle $article): JsonResponse
    {
        $article->incrementViewCount();

        return response()->json($article->load(['category', 'author', 'versions.author']));
    }

    public function update(Request $request, KnowledgeBaseArticle $article): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'category_id' => 'sometimes|exists:knowledge_base_categories,id',
            'status' => 'sometimes|in:draft,in_review,approved,published,archived',
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $article->update($validated);

        if ($validated['status'] ?? null === 'published' && !$article->published_at) {
            $article->update(['published_at' => now()]);
            $this->knowledgeBaseService->createVersion($article, Auth::user());
        }

        return response()->json($article->fresh()->load(['category', 'author']));
    }

    public function destroy(KnowledgeBaseArticle $article): JsonResponse
    {
        $article->delete();

        return response()->json(null, 204);
    }

    public function rate(Request $request, KnowledgeBaseArticle $article): JsonResponse
    {
        $validated = $request->validate([
            'helpful' => 'required|boolean',
        ]);

        $article->recordHelpfulVote($validated['helpful']);

        return response()->json(['message' => 'Thank you for your feedback.']);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q');
        $limit = $request->get('limit', 10);

        $articles = KnowledgeBaseArticle::published()
            ->where('title', 'like', "%{$query}%")
            ->orWhere('body', 'like', "%{$query}%")
            ->limit($limit)
            ->get(['id', 'title', 'slug']);

        return response()->json($articles);
    }

    public function restoreVersion(Request $request, KnowledgeBaseArticle $article): JsonResponse
    {
        $validated = $request->validate([
            'version_id' => 'required|exists:knowledge_base_article_versions,id',
        ]);

        $version = $article->versions()->findOrFail($validated['version_id']);
        $this->knowledgeBaseService->restoreVersion($article, $version, Auth::user());

        return response()->json($article->fresh());
    }
}
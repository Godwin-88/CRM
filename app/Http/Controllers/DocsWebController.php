<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\UserDocChecklist;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocsWebController extends Controller
{
    public function index()
    {
        $categories = KnowledgeBaseCategory::withCount(['articles' => function ($query) {
            $query->where('status', 'published');
        }])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Docs/Index', [
            'categories' => $categories,
        ]);
    }

    public function category(KnowledgeBaseCategory $category)
    {
        $articles = $category->articles()
            ->where('status', 'published')
            ->with('author')
            ->orderBy('published_at', 'desc')
            ->get();

        return Inertia::render('Docs/Category', [
            'category' => $category,
            'articles' => $articles,
        ]);
    }

    public function show(KnowledgeBaseArticle $article)
    {
        $article->incrementViewCount();

        return Inertia::render('Docs/Show', [
            'article' => $article->load(['category', 'author']),
        ]);
    }

    public function verify(Request $request, KnowledgeBaseArticle $article)
    {
        $article->update(['last_verified_at' => now()]);

        return back()->with('success', 'Article verified.');
    }

    public function onboardingChecklist(Request $request)
    {
        $user = $request->user();
        $audience = $this->deriveAudience($user);

        $checklistItems = config("docs.checklist_items.{$audience}", []);

        $userChecklists = UserDocChecklist::where('user_id', $user->id)
            ->whereIn('checklist_item_key', array_keys($checklistItems))
            ->get()
            ->keyBy('checklist_item_key');

        $checklist = collect($checklistItems)->map(function ($item, $key) use ($userChecklists) {
            $userChecklist = $userChecklists->get($key);
            return [
                'key' => $key,
                ...$item,
                'completed' => $userChecklist?->isCompleted() ?? false,
                'dismissed' => $userChecklist?->isDismissed() ?? false,
            ];
        })->values();

        if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['checklist' => $checklist]);
        }

        return Inertia::render('Onboarding/Checklist', [
            'checklist' => $checklist,
        ]);
    }

    public function completeItem(Request $request)
    {
        $validated = $request->validate([
            'checklist_item_key' => 'required|string',
        ]);

        $userChecklist = UserDocChecklist::firstOrCreate([
            'user_id' => $request->user()->id,
            'checklist_item_key' => $validated['checklist_item_key'],
        ]);

        $userChecklist->markCompleted();

        return response()->json(['completed' => true]);
    }

    public function dismissChecklist(Request $request)
    {
        $validated = $request->validate([
            'checklist_item_key' => 'required|string',
        ]);

        $userChecklist = UserDocChecklist::firstOrCreate([
            'user_id' => $request->user()->id,
            'checklist_item_key' => $validated['checklist_item_key'],
        ]);

        $userChecklist->markDismissed();

        return response()->json(['dismissed' => true]);
    }

    protected function deriveAudience($user): string
    {
        $roles = $user->roles->pluck('name')->toArray();

        if (in_array('admin', $roles)) {
            return 'admin';
        }

        if (in_array('manager', $roles)) {
            return 'manager';
        }

        if (in_array('finance-manager', $roles)) {
            return 'finance-manager';
        }

        if (in_array('operations-manager', $roles)) {
            return 'operations-manager';
        }

        return 'agent';
    }
}
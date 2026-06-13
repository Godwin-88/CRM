<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KnowledgeBaseCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = KnowledgeBaseCategory::query()
            ->with('children');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        return response()->json($query->orderBy('sort_order')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:knowledge_base_categories,id',
            'sort_order' => 'sometimes|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category = KnowledgeBaseCategory::create($validated);

        return response()->json($category, 201);
    }

    public function show(KnowledgeBaseCategory $category): JsonResponse
    {
        return response()->json($category->load(['children', 'articles']));
    }

    public function update(Request $request, KnowledgeBaseCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'sometimes|nullable|exists:knowledge_base_categories,id',
            'sort_order' => 'sometimes|integer',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return response()->json($category->fresh());
    }

    public function destroy(KnowledgeBaseCategory $category): JsonResponse
    {
        $category->delete();

        return response()->json(null, 204);
    }
}

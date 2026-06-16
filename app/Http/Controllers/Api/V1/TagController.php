<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tag::query();

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        $tags = $query->orderBy('name')->limit(100)->get(['id', 'name', 'type', 'usage_count']);

        return response()->json($tags);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:tags,name',
            'type' => 'nullable|string|max:50',
        ]);

        $tag = Tag::create([
            ...$validated,
            'usage_count' => 0,
        ]);

        return response()->json($tag, 201);
    }

    public function update(Request $request, Tag $tag): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100|unique:tags,name,' . $tag->id,
            'type' => 'sometimes|string|max:50',
        ]);

        $tag->update($validated);

        return response()->json($tag);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json(null, 204);
    }

    public function bulkApply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_ids' => 'required|array',
            'campaign_ids.*' => 'exists:campaigns,id',
            'tags' => 'required|array',
            'tags.*' => 'string|max:100',
            'operation' => 'required|in:add,remove',
        ]);

        $campaigns = \App\Models\Campaign::whereIn('id', $validated['campaign_ids'])->get();
        $operation = $validated['operation'];

        foreach ($campaigns as $campaign) {
            $currentTags = $campaign->tags ?? [];

            if ($operation === 'add') {
                $currentTags = array_unique(array_merge($currentTags, $validated['tags']));
            } else {
                $currentTags = array_values(array_diff($currentTags, $validated['tags']));
            }

            $campaign->update(['tags' => $currentTags]);

            foreach ($validated['tags'] as $tagName) {
                if ($operation === 'add') {
                    Tag::firstOrCreate(['name' => $tagName], ['type' => 'campaign', 'usage_count' => 1]);
                    Tag::where('name', $tagName)->increment('usage_count');
                }
            }
        }

        return response()->json([
            'updated' => $campaigns->count(),
            'operation' => $operation,
            'tags' => $validated['tags'],
        ]);
    }
}

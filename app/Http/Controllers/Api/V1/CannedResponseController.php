<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CannedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CannedResponseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CannedResponse::query()->withTrashed()->active();

        if ($request->filled('category_tag')) {
            $query->where('category_tag', $request->category_tag);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                ->orWhere('body', 'like', '%' . $request->search . '%');
        }

        $sortField = $request->get('sort', 'usage_count');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        return response()->json($query->paginate($request->get('per_page', 50)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category_tag' => 'nullable|string',
        ]);

        $response = CannedResponse::create($validated);

        return response()->json($response, 201);
    }

    public function show(CannedResponse $cannedResponse): JsonResponse
    {
        return response()->json($cannedResponse);
    }

    public function update(Request $request, CannedResponse $cannedResponse): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'category_tag' => 'sometimes|nullable|string',
        ]);

        $cannedResponse->update($validated);

        return response()->json($cannedResponse->fresh());
    }

    public function destroy(CannedResponse $cannedResponse): JsonResponse
    {
        $cannedResponse->delete();

        return response()->json(null, 204);
    }

    public function toggleActive(CannedResponse $cannedResponse): JsonResponse
    {
        $cannedResponse->update(['is_active' => !$cannedResponse->is_active]);

        return response()->json($cannedResponse->fresh());
    }

    public function favorite(CannedResponse $cannedResponse): JsonResponse
    {
        $cannedResponse->favoritedBy()->attach(Auth::id());

        $cannedResponse->incrementUsage();

        return response()->json(['message' => 'Added to favorites.']);
    }

    public function unfavorite(CannedResponse $cannedResponse): JsonResponse
    {
        $cannedResponse->favoritedBy()->detach(Auth::id());

        return response()->json(['message' => 'Removed from favorites.']);
    }

    public function bulkDeactivateByCategory(string $category): JsonResponse
    {
        CannedResponse::where('category_tag', $category)->update(['is_active' => false]);

        return response()->json(['message' => "Deactivated all responses in {$category}."]);
    }

    public static function resolveVariables(string $body, array $variables): string
    {
        return str_replace(
            array_map(fn($v) => "{{{$v}}}", array_keys($variables)),
            array_values($variables),
            $body
        );
    }
}
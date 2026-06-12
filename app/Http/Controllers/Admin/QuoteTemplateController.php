<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:manager|admin');
    }

    public function index(): JsonResponse
    {
        return response()->json(QuoteTemplate::paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $template = QuoteTemplate::create($validated);

        return response()->json($template, 201);
    }

    public function update(Request $request, QuoteTemplate $quoteTemplate): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $quoteTemplate->update($validated);

        return response()->json($quoteTemplate);
    }

    public function destroy(QuoteTemplate $quoteTemplate): JsonResponse
    {
        if ($quoteTemplate->quotes()->exists()) {
            return response()->json([
                'message' => 'Cannot delete template used in quotes.',
            ], 422);
        }

        $quoteTemplate->delete();
        return response()->json(null, 204);
    }
}
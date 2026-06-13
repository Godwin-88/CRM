<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CannedResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CannedResponseController extends Controller
{
    public function index()
    {
        $responses = CannedResponse::withTrashed()
            ->orderBy('usage_count', 'desc')
            ->paginate(50);

        $categories = CannedResponse::distinct()->pluck('category_tag');

        return Inertia::render('Admin/Support/CannedResponses', [
            'responses' => $responses,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category_tag' => 'nullable|string',
        ]);

        $response = CannedResponse::create($validated);

        return redirect()->route('admin.support.canned-responses.index')
            ->with('success', 'Canned response created successfully.');
    }

    public function update(Request $request, CannedResponse $cannedResponse)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'category_tag' => 'sometimes|nullable|string',
        ]);

        $cannedResponse->update($validated);

        return redirect()->route('admin.support.canned-responses.index')
            ->with('success', 'Canned response updated successfully.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteTemplate;
use App\Models\Quote;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class QuoteTemplateWebController extends Controller
{
    public function index(): Response
    {
        $templates = QuoteTemplate::with(['products'])->orderBy('name')->get();

        return Inertia::render('Admin/QuoteTemplates', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'products' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        QuoteTemplate::create([
            'name' => $request->name,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('admin.quote-templates.index')->with('success', 'Quote template created successfully.');
    }

    public function update(Request $request, QuoteTemplate $quoteTemplate)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $quoteTemplate->update($request->all());

        return redirect()->route('admin.quote-templates.index')->with('success', 'Quote template updated successfully.');
    }

    public function quotes(): Response
    {
        $quotes = Quote::with(['contact', 'deal'])->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/Quotes', [
            'quotes' => $quotes,
        ]);
    }
}

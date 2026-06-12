<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteTemplate;
use App\Models\Quote;
use App\Models\QuoteLineItem;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuoteWebController extends Controller
{
    public function index(): Response
    {
        $quotes = Quote::with(['contact', 'deal', 'items'])->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/Quotes', [
            'quotes' => $quotes,
        ]);
    }

    public function create(): Response
    {
        $templates = QuoteTemplate::where('is_active', true)->get();
        $contacts = Contact::orderBy('first_name')->get();
        $deals = Deal::with('contact')->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/QuotesCreate', [
            'templates' => $templates,
            'contacts' => $contacts,
            'deals' => $deals,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'deal_id' => 'nullable|exists:deals,id',
            'status' => 'nullable|in:draft,sent,accepted,rejected',
            'notes' => 'nullable|string|max:2000',
            'valid_until' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $quote = Quote::create($request->only(['contact_id', 'deal_id', 'status', 'notes', 'valid_until']));

        foreach ($request->items as $item) {
            $quote->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return redirect()->route('admin.quotes.index')->with('success', 'Quote created successfully.');
    }

    public function show(Quote $quote): Response
    {
        $quote->load(['contact', 'deal', 'items']);

        return Inertia::render('Admin/QuotesShow', [
            'quote' => $quote,
        ]);
    }

    public function updateStatus(Request $request, Quote $quote)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,accepted,rejected,expired',
        ]);

        $quote->update(['status' => $request->status]);

        return back()->with('success', 'Quote status updated successfully.');
    }
}

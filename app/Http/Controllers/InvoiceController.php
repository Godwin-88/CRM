<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->with(['account', 'contact', 'deals'])
            ->when($request->filled('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('account', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('contact', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->filled('account_id'), fn ($q, $account) => $q->where('account_id', $account))
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->appends($request->query());

        $accounts = Account::select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'accounts' => $accounts,
            'filters' => $request->only(['search', 'status', 'account_id']),
            'statuses' => [
                Invoice::STATUS_DRAFT,
                Invoice::STATUS_SENT,
                Invoice::STATUS_PARTIALLY_PAID,
                Invoice::STATUS_PAID,
                Invoice::STATUS_OVERDUE,
                Invoice::STATUS_CANCELLED,
            ],
        ]);
    }

    public function show(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['account', 'contact', 'deals', 'lineItems', 'payments' => fn ($q) => $q->orderBy('payment_date', 'desc')]);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
            'canPay' => auth()->user()->can('recordPayment', $invoice),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Invoice::class);

        $accounts = Account::select(['id', 'name'])->orderBy('name')->get();
        $contacts = Contact::select(['id', 'first_name', 'last_name'])->orderBy('last_name')->get();
        $deals = Deal::query()->selectRaw('id, title as name, contact_id')->orderBy('title')->get();

        return Inertia::render('Invoices/Create', [
            'accounts' => $accounts,
            'contacts' => $contacts,
            'deals' => $deals,
            'nextInvoiceNumber' => Invoice::generateInvoiceNumber(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Invoice::class);

        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'due_date' => ['required', 'date'],
            'currency' => ['required', 'string', 'max:3'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.description' => ['required', 'string'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'deal_ids' => ['nullable', 'array'],
        ]);

        $invoice = Invoice::create([
            'account_id' => $validated['account_id'],
            'contact_id' => $validated['contact_id'] ?? null,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'status' => Invoice::STATUS_DRAFT,
            'currency' => $validated['currency'],
            'due_date' => $validated['due_date'],
        ]);

        $subtotal = 0;
        $totalTax = 0;

        foreach ($validated['line_items'] as $item) {
            $totals = InvoiceLineItem::calculateTotals(
                $item['quantity'],
                $item['unit_price'],
                $item['tax_rate'] ?? 0
            );

            $invoice->lineItems()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'line_total' => $totals['line_total'],
                'tax_amount' => $totals['tax_amount'],
            ]);

            $subtotal += $totals['line_total'];
            $totalTax += $totals['tax_amount'];
        }

        $invoice->update([
            'subtotal' => $subtotal,
            'total_tax' => $totalTax,
            'total' => $subtotal + $totalTax,
        ]);

        if (! empty($validated['deal_ids'])) {
            $invoice->deals()->sync($validated['deal_ids']);
        }

        \activity()
            ->performedOn($invoice)
            ->withProperties(['invoice_number' => $invoice->invoice_number])
            ->log('invoice_created');

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function edit(Invoice $invoice): Response
    {
        $this->authorize('update', $invoice);

        $invoice->load(['lineItems', 'deals']);
        $accounts = Account::select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'accounts' => $accounts,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'account_id' => ['sometimes', 'exists:accounts,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'due_date' => ['sometimes', 'date'],
            'currency' => ['sometimes', 'string', 'max:3'],
            'line_items' => ['sometimes', 'array', 'min:1'],
            'deal_ids' => ['nullable', 'array'],
        ]);

        $invoice->update($validated);

        if (isset($validated['line_items'])) {
            $invoice->lineItems()->delete();

            $subtotal = 0;
            $totalTax = 0;

            foreach ($validated['line_items'] as $item) {
                $totals = InvoiceLineItem::calculateTotals(
                    $item['quantity'],
                    $item['unit_price'],
                    $item['tax_rate'] ?? 0
                );

                $invoice->lineItems()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'line_total' => $totals['line_total'],
                    'tax_amount' => $totals['tax_amount'],
                ]);

                $subtotal += $totals['line_total'];
                $totalTax += $totals['tax_amount'];
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'total_tax' => $totalTax,
                'total' => $subtotal + $totalTax,
            ]);
        }

        if (isset($validated['deal_ids'])) {
            $invoice->deals()->sync($validated['deal_ids'] ?? []);
        }

        \activity()
            ->performedOn($invoice)
            ->withProperties(['invoice_id' => $invoice->id])
            ->log('invoice_updated');

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        \activity()
            ->performedOn($invoice)
            ->withProperties(['invoice_id' => $invoice->id])
            ->log('invoice_deleted');

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    public function send(Invoice $invoice)
    {
        $this->authorize('send', $invoice);

        $invoice->update([
            'status' => Invoice::STATUS_SENT,
            'sent_at' => now(),
        ]);

        \activity()
            ->performedOn($invoice)
            ->log('invoice_sent');

        return back()->with('success', 'Invoice sent.');
    }

    public function downloadPdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $path = $invoice->pdf_path ?? "invoices/{$invoice->account_id}/{$invoice->id}/INV-{$invoice->invoice_number}.pdf";

        if (! \Storage::disk('r2')->exists($path)) {
            abort(404, 'Invoice PDF not found.');
        }

        if (request()->wantsJson()) {
            return response()->json([
                'url' => \Storage::disk('r2')->temporaryUrl($path, now()->addMinutes(15)),
            ]);
        }

        return redirect(\Storage::disk('r2')->temporaryUrl($path, now()->addMinutes(15)));
    }

    public function recordPayment(Request $request, Invoice $invoice)
    {
        $this->authorize('recordPayment', $invoice);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:bank_transfer,card,mobile_money,cash,other'],
            'reference_number' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $invoice->payments()->create($validated);

        $invoice->updateStatusBasedOnPayments();

        \activity()
            ->performedOn($invoice)
            ->withProperties($validated)
            ->log('invoice_payment_recorded');

        return back()->with('success', 'Payment recorded.');
    }
}

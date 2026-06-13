<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $po = QueryBuilder::for(PurchaseOrder::query())
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('vendor_id'),
            ])
            ->with(['vendor', 'approver'])
            ->when($request->filled('search'), function ($query, $search) {
                $query->where('po_number', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->appends($request->query());

        $vendors = Vendor::select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders' => $po,
            'vendors' => $vendors,
            'filters' => $request->only(['search', 'status', 'vendor_id']),
            'statuses' => [
                PurchaseOrder::STATUS_DRAFT,
                PurchaseOrder::STATUS_SUBMITTED,
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                PurchaseOrder::STATUS_RECEIVED,
                PurchaseOrder::STATUS_CANCELLED,
            ],
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load([
            'vendor',
            'approver',
            'lineItems',
            'goodsReceipts.items',
            'vendorInvoices.items',
        ]);

        $canApprove = auth()->user()->can('approve', $purchaseOrder);
        $canReceive = auth()->user()->can('recordReceipt', $purchaseOrder);

        return Inertia::render('PurchaseOrders/Show', [
            'purchaseOrder' => $purchaseOrder,
            'canApprove' => $canApprove,
            'canReceive' => $canReceive,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PurchaseOrder::class);

        $vendors = Vendor::select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('PurchaseOrders/Create', [
            'vendors' => $vendors,
            'nextPoNumber' => PurchaseOrder::generatePoNumber(),
            'paymentMethods' => ['goods', 'services', 'both'],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', PurchaseOrder::class);

        $validated = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'required_by_date' => ['required', 'date'],
            'currency' => ['required', 'string', 'max:3'],
            'category' => ['nullable', 'string'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.description' => ['required', 'string'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $vendor = Vendor::findOrFail($validated['vendor_id']);
        if ($vendor->status === Vendor::STATUS_BLACKLISTED) {
            return back()->withErrors(['vendor_id' => 'Selected vendor is blacklisted.']);
        }

        $po = PurchaseOrder::create([
            'po_number' => PurchaseOrder::generatePoNumber(),
            'vendor_id' => $validated['vendor_id'],
            'status' => PurchaseOrder::STATUS_DRAFT,
            'currency' => $validated['currency'],
            'required_by_date' => $validated['required_by_date'],
            'category' => $validated['category'] ?? null,
        ]);

        $subtotal = 0;
        $totalTax = 0;

        foreach ($validated['line_items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $taxAmount = $lineTotal * (($item['tax_rate'] ?? 0) / 100);

            $po->lineItems()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'line_total' => $lineTotal,
                'tax_amount' => $taxAmount,
            ]);

            $subtotal += $lineTotal;
            $totalTax += $taxAmount;
        }

        $po->update([
            'subtotal' => $subtotal,
            'total_tax' => $totalTax,
            'total' => $subtotal + $totalTax,
        ]);

        \activity()
            ->performedOn($po)
            ->withProperties(['po_number' => $po->po_number])
            ->log('purchase_order_created');

        return redirect()->route('purchase-orders.show', $po)->with('success', 'PO created.');
    }

    public function submit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $purchaseOrder->update([
            'status' => PurchaseOrder::STATUS_SUBMITTED,
        ]);

        \activity()
            ->performedOn($purchaseOrder)
            ->log('purchase_order_submitted');

        return back()->with('success', 'PO submitted for approval.');
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('approve', $purchaseOrder);

        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
            'rejection_reason' => ['required_if:approved,false', 'string'],
        ]);

        if ($validated['approved']) {
            $purchaseOrder->update([
                'status' => PurchaseOrder::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'rejection_reason' => null,
            ]);

            \activity()
                ->performedOn($purchaseOrder)
                ->log('purchase_order_approved');

            return back()->with('success', 'PO approved.');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrder::STATUS_DRAFT,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        \activity()
            ->performedOn($purchaseOrder)
            ->withProperties(['reason' => $validated['rejection_reason']])
            ->log('purchase_order_rejected');

        return back()->with('success', 'PO rejected.');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('recordReceipt', $purchaseOrder);

        $validated = $request->validate([
            'receipt_date' => ['required', 'date'],
            'items' => ['required', 'array'],
            'items.*.po_line_item_id' => ['required', 'exists:po_line_items,id'],
            'items.*.received_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $receipt = $purchaseOrder->goodsReceipts()->create([
            'receipt_number' => 'GR-'.now()->format('YmdHis'),
            'receipt_date' => $validated['receipt_date'],
        ]);

        foreach ($validated['items'] as $item) {
            $receipt->items()->create([
                'po_line_item_id' => $item['po_line_item_id'],
                'received_quantity' => $item['received_quantity'],
            ]);
        }

        $receivedQty = $purchaseOrder->getTotalReceivedAttribute();
        $totalQty = $purchaseOrder->lineItems->sum('quantity');

        if ($receivedQty >= $totalQty) {
            $purchaseOrder->update(['status' => PurchaseOrder::STATUS_RECEIVED, 'received_at' => now()]);
        } else {
            $purchaseOrder->update(['status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED]);
        }

        \activity()
            ->performedOn($purchaseOrder)
            ->withProperties(['receipt_number' => $receipt->receipt_number])
            ->log('goods_received');

        return back()->with('success', 'Goods receipt recorded.');
    }

    public function linkVendorInvoice(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $validated = $request->validate([
            'vendor_invoice_number' => ['required', 'string'],
            'total' => ['required', 'numeric'],
            'invoice_date' => ['required', 'date'],
            'items' => ['nullable', 'array'],
            'items.*.po_line_item_id' => ['required', 'exists:po_line_items,id'],
            'items.*.invoiced_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $vendorInvoice = $purchaseOrder->vendorInvoices()->create([
            'vendor_invoice_number' => $validated['vendor_invoice_number'],
            'total' => $validated['total'],
            'invoice_date' => $validated['invoice_date'],
        ]);

        if (! empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $vendorInvoice->items()->create([
                    'po_line_item_id' => $item['po_line_item_id'],
                    'invoiced_quantity' => $item['invoiced_quantity'],
                ]);
            }
        }

        \activity()
            ->performedOn($purchaseOrder)
            ->log('vendor_invoice_linked');

        return back()->with('success', 'Vendor invoice linked.');
    }
}

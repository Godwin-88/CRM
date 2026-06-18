<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::query()
            ->with(['account', 'contact']);

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $query->orderByDesc('created_at');

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function ledger(Request $request, string $account_id): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::where('account_id', $account_id)
            ->orderByDesc('due_date')
            ->get();

        $totalInvoiced = $invoices->sum('total');
        $totalPaid = $invoices->sum(function ($invoice) {
            return $invoice->getTotalPaidAttribute();
        });

        return response()->json([
            'account_id' => $account_id,
            'total_invoiced' => (float) $totalInvoiced,
            'total_paid' => (float) $totalPaid,
            'outstanding' => (float) $totalInvoiced - (float) $totalPaid,
            'invoice_count' => $invoices->count(),
            'invoices' => $invoices->map(fn ($i) => [
                'id' => $i->id,
                'invoice_number' => $i->invoice_number,
                'status' => $i->status,
                'total' => $i->total,
                'paid' => $i->getTotalPaidAttribute(),
                'due_date' => $i->due_date,
            ]),
        ]);
    }
}

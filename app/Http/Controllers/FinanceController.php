<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $this->authorize('view', Invoice::class);

        $startDate = $request->get('start_date', now()->subMonths(12)->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());
        $currency = $request->get('currency');
        $accountType = $request->get('account_type');

        $invoices = Invoice::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($currency, fn ($q) => $q->where('currency', $currency))
            ->get();

        $totalInvoiced = (float) $invoices->whereIn('status', ['sent', 'partially_paid', 'paid', 'overdue'])->sum('total');

        $payments = Payment::query()
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get();

        $totalCollected = (float) $payments->sum('amount');

        $totalOutstanding = $totalInvoiced - $totalCollected;

        $collectionRate = $totalInvoiced > 0 ? round(($totalCollected / $totalInvoiced) * 100, 2) : 0;

        $overdueInvoices = Invoice::where('status', 'overdue')
            ->when($currency, fn ($q) => $q->where('currency', $currency))
            ->get();

        $overdueCount = $overdueInvoices->count();
        $overdueValue = (float) $overdueInvoices->sum('total');

        $poSpend = (float) PurchaseOrder::where('status', 'received')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($currency, fn ($q) => $q->where('currency', $currency))
            ->sum('total');

        $revenueTrend = Invoice::query()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, sum(total) as invoiced, sum(total) as collected')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->get();

        $agingBuckets = [
            'current' => Invoice::where('status', 'paid')->sum('total'),
            'days_31_60' => 0,
            'days_61_90' => 0,
            'over_90' => 0,
        ];

        Invoice::whereIn('status', ['sent', 'partially_paid', 'overdue'])
            ->get()
            ->each(function ($invoice) use (&$agingBuckets) {
                $daysOverdue = now()->diffInDays($invoice->due_date, false);
                if ($daysOverdue <= 0) {
                    $agingBuckets['current'] += $invoice->total;
                } elseif ($daysOverdue <= 30) {
                    $agingBuckets['days_31_60'] += $invoice->total;
                } elseif ($daysOverdue <= 60) {
                    $agingBuckets['days_61_90'] += $invoice->total;
                } else {
                    $agingBuckets['over_90'] += $invoice->total;
                }
            });

        $topAccounts = Account::withCount(['invoices as outstanding_balance'])
            ->withSum('invoices as total_invoiced', 'total')
            ->withSum('invoices as total_paid', 'total')
            ->orderByDesc('outstanding_balance')
            ->limit(10)
            ->get();

        $vendorCategories = PurchaseOrder::where('status', 'received')
            ->join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
            ->selectRaw('vendors.category, sum(purchase_orders.total) as total_spend')
            ->groupBy('vendors.category')
            ->pluck('total_spend', 'category');

        $topVendors = PurchaseOrder::where('status', 'received')
            ->with('vendor')
            ->selectRaw('vendor_id, sum(total) as total_spend')
            ->groupBy('vendor_id')
            ->orderByDesc('total_spend')
            ->limit(5)
            ->get();

        return Inertia::render('Finance/Dashboard', [
            'metrics' => [
                'total_invoiced' => $totalInvoiced,
                'total_collected' => $totalCollected,
                'total_outstanding' => $totalOutstanding,
                'collection_rate' => $collectionRate,
                'overdue_count' => $overdueCount,
                'overdue_value' => $overdueValue,
                'po_spend' => $poSpend,
            ],
            'revenueTrend' => $revenueTrend,
            'agingBuckets' => $agingBuckets,
            'topAccounts' => $topAccounts,
            'vendorSpend' => $vendorCategories,
            'topVendors' => $topVendors,
            'filters' => $request->only(['start_date', 'end_date', 'currency', 'account_type']),
            'currencies' => Invoice::distinct()->pluck('currency'),
            'lastCalculated' => cache()->get('finance_dashboard_last_calculated'),
        ]);
    }

    public function refreshDashboard()
    {
        cache()->put('finance_dashboard_last_calculated', now()->toIso8601String(), 3600);

        return back()->with('success', 'Dashboard metrics refreshed.');
    }
}

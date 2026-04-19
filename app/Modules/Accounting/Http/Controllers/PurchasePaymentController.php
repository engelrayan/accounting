<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\PurchasePayment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchasePaymentController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;
        $method    = $request->query('method');
        $from      = $request->query('from');
        $to        = $request->query('to');

        $query = PurchasePayment::forCompany($companyId)
            ->with([
                'vendor:id,name',
                'purchaseInvoice:id,invoice_number',
            ])
            ->when($method, fn ($q) => $q->where('payment_method', $method))
            ->when($from,   fn ($q) => $q->where('payment_date', '>=', $from))
            ->when($to,     fn ($q) => $q->where('payment_date', '<=', $to))
            ->orderByDesc('payment_date')
            ->orderByDesc('id');

        $payments = $query->paginate(25)->withQueryString();

        // Totals for filtered results
        $totalAmount = PurchasePayment::forCompany($companyId)
            ->when($method, fn ($q) => $q->where('payment_method', $method))
            ->when($from,   fn ($q) => $q->where('payment_date', '>=', $from))
            ->when($to,     fn ($q) => $q->where('payment_date', '<=', $to))
            ->sum('amount');

        // Method breakdown for filter period
        $methodTotals = PurchasePayment::forCompany($companyId)
            ->when($from, fn ($q) => $q->where('payment_date', '>=', $from))
            ->when($to,   fn ($q) => $q->where('payment_date', '<=', $to))
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as cnt')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        return view('accounting.purchase-payments.index', compact(
            'payments',
            'totalAmount',
            'methodTotals',
            'method',
            'from',
            'to',
        ));
    }
}

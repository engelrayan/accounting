<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;
        $method    = $request->query('method');
        $from      = $request->query('from');
        $to        = $request->query('to');

        $payments = Payment::forCompany($companyId)
            ->with(['customer:id,name', 'invoice:id,invoice_number,amount'])
            ->when($method, fn ($q) => $q->where('payment_method', $method))
            ->when($from,   fn ($q) => $q->where('payment_date', '>=', $from))
            ->when($to,     fn ($q) => $q->where('payment_date', '<=', $to))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(30);

        $totalThisPage = $payments->sum('amount');

        $totalAll = Payment::forCompany($companyId)
            ->when($method, fn ($q) => $q->where('payment_method', $method))
            ->when($from,   fn ($q) => $q->where('payment_date', '>=', $from))
            ->when($to,     fn ($q) => $q->where('payment_date', '<=', $to))
            ->sum('amount');

        // Method counts for the filter bar
        $methodCounts = Payment::forCompany($companyId)
            ->selectRaw('payment_method, count(*) as total, sum(amount) as sum_amount')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        return view('accounting.payments.index', compact(
            'payments',
            'totalAll',
            'methodCounts',
            'method',
            'from',
            'to',
        ));
    }
}

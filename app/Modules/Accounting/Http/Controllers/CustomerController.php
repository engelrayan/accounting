<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Customer;
use App\Modules\Accounting\Models\Invoice;
use App\Modules\Accounting\Services\ActivityLogService;
use App\Modules\Accounting\Services\CustomerService;
use App\Modules\Accounting\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService    $service,
        private readonly InvoiceService     $invoiceService,
        private readonly ActivityLogService $log,
    ) {}

    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $companyId      = $request->user()->company_id;
        $filterOutstanding = $request->boolean('outstanding');

        $customers = Customer::forCompany($companyId)
            ->withCount('invoices')
            ->orderBy('name')
            ->get();

        // Single aggregate query for all customers — eliminates N+1
        $aggregates = $this->service->bulkAggregates($companyId);
        $customers->each(function (Customer $c) use ($aggregates) {
            $agg              = $aggregates->get($c->id);
            $totalInvoiced    = (float) ($agg->total_invoiced ?? 0);
            $totalPaid        = (float) ($agg->total_paid     ?? 0);
            $c->totalInvoiced = $totalInvoiced;
            $c->balance       = (float) $c->opening_balance + $totalInvoiced - $totalPaid;
        });

        // Filter to customers with outstanding balance if requested
        if ($filterOutstanding) {
            $customers = $customers->filter(fn ($c) => $c->balance > 0)->values();
        }

        // Sort: customers with a balance first, then alphabetically within each group
        $customers = $customers
            ->sortByDesc(fn ($c) => $c->balance)
            ->values();

        $totalReceivable   = $customers->sum(fn ($c) => max(0, $c->balance));
        $withBalanceCount  = $customers->filter(fn ($c) => $c->balance > 0)->count();
        $settledCount      = $customers->filter(fn ($c) => $c->balance <= 0)->count();

        return view('accounting.customers.index', compact(
            'customers',
            'totalReceivable',
            'filterOutstanding',
            'withBalanceCount',
            'settledCount',
        ));
    }

    // -------------------------------------------------------------------------

    public function create(): View
    {
        Gate::authorize('can-write');
        return view('accounting.customers.create');
    }

    // -------------------------------------------------------------------------

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'phone'           => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
            'address'         => 'nullable|string|max:1000',
            'opening_balance' => 'nullable|numeric|min:0',
        ], [
            'name.required'   => 'اسم العميل مطلوب.',
            'email.email'     => 'البريد الإلكتروني غير صالح.',
        ]);

        $customer = $this->service->createCustomer($validated, $request->user()->company_id);

        $this->log->log(
            $request->user()->company_id,
            'created', 'customer', $customer->id,
            $customer->name,
            "أنشأ عميلاً جديداً [{$customer->name}]."
        );

        return redirect()
            ->route('accounting.customers.show', $customer)
            ->with('success', "تم إنشاء العميل [{$customer->name}] بنجاح.");
    }

    // -------------------------------------------------------------------------

    public function show(Request $request, Customer $customer): View
    {
        $this->authorizeCompany($request, $customer);

        // Eager-load: formal invoices + their payments; payment history with invoice reference
        $customer->load([
            'invoices',
            'invoices.payments',
            'payments',
            'payments.invoice:id,invoice_number',
        ]);

        $balance       = $this->service->getBalance($customer);
        $totalInvoiced = $this->service->getTotalInvoiced($customer);
        $totalPaid     = $this->service->getTotalPaid($customer);

        // Unpaid = any invoice with remaining > 0 (uses loaded payments — no extra queries)
        $unpaidInvoices  = $customer->invoices->filter(fn ($inv) => $inv->isUnpaid());
        $overdueInvoices = $customer->invoices->filter(fn ($inv) => $inv->isOverdue());

        // Dropdown for payment form: invoices still outstanding
        $pendingInvoices = $unpaidInvoices;

        return view('accounting.customers.show', compact(
            'customer',
            'balance',
            'totalInvoiced',
            'totalPaid',
            'unpaidInvoices',
            'overdueInvoices',
            'pendingInvoices',
        ));
    }

    // -------------------------------------------------------------------------

    public function storePayment(Request $request, Customer $customer): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $customer);

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|in:cash,bank,wallet,instapay,cheque,card,other',
            'invoice_id'     => [
                'required',
                'integer',
                Rule::exists('invoices', 'id')->where('customer_id', $customer->id),
            ],
            'notes' => 'nullable|string|max:500',
        ], [
            'amount.required'         => 'المبلغ مطلوب.',
            'payment_date.required'   => 'تاريخ الدفع مطلوب.',
            'payment_method.required' => 'طريقة الدفع مطلوبة.',
            'payment_method.in'       => 'طريقة الدفع غير صالحة.',
            'invoice_id.required'     => 'يجب تحديد الفاتورة المرتبطة بالدفعة.',
            'invoice_id.exists'       => 'الفاتورة المحددة غير موجودة.',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);

        try {
            $payment = $this->invoiceService->recordPayment($invoice, [
                'amount'         => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_date'   => $validated['payment_date'],
                'notes'          => $validated['notes'] ?? null,
            ]);
        } catch (\DomainException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        $this->log->log(
            $request->user()->company_id,
            'created', 'payment', $payment->id,
            $customer->name,
            "سجَّل دفعة بمبلغ {$payment->amount} من العميل [{$customer->name}] على الفاتورة [{$invoice->invoice_number}]."
        );

        return back()->with('success', 'تم تسجيل الدفعة بنجاح.');
    }

    // -------------------------------------------------------------------------

    public function settle(Request $request, Customer $customer): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $customer);

        $validated = $request->validate([
            'settlement_type' => 'required|in:discount,bad_debt',
        ], [
            'settlement_type.required' => 'نوع التسوية مطلوب.',
            'settlement_type.in'       => 'نوع التسوية غير صالح.',
        ]);

        $result = $this->service->settleAccount($customer, $validated['settlement_type']);

        if ($result['invoiceCount'] === 0) {
            return back()->with('info', 'لا توجد فواتير مستحقة — الحساب مسوَّى بالفعل.');
        }

        $typeLabel = $validated['settlement_type'] === 'bad_debt' ? 'إعدام دين' : 'خصم';

        $this->log->log(
            $request->user()->company_id,
            'settled', 'customer', $customer->id,
            $customer->name,
            "تسوية حساب [{$customer->name}] ({$typeLabel}): {$result['invoiceCount']} فاتورة بإجمالي {$result['totalAmount']}."
        );

        return back()->with(
            'success',
            "تمت تسوية الحساب ({$typeLabel}) بنجاح. تم تسديد {$result['invoiceCount']} " .
            ($result['invoiceCount'] === 1 ? 'فاتورة' : 'فواتير') .
            " بإجمالي " . number_format($result['totalAmount'], 2) . " وتسجيل قيد محاسبي."
        );
    }

    // -------------------------------------------------------------------------

    private function authorizeCompany(Request $request, Customer $customer): void
    {
        abort_if($customer->company_id !== $request->user()->company_id, 403);
    }
}

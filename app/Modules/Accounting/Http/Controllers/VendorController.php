<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Vendor;
use App\Modules\Accounting\Services\ActivityLogService;
use App\Modules\Accounting\Services\VendorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(
        private readonly VendorService          $service,
        private readonly ActivityLogService     $log,
    ) {}

    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $companyId         = $request->user()->company_id;
        $filterOutstanding = $request->boolean('outstanding');

        $vendors = Vendor::forCompany($companyId)
            ->withCount('purchaseInvoices')
            ->orderBy('name')
            ->get();

        // Single aggregate query for all vendors — eliminates N+1
        $aggregates = $this->service->bulkAggregates($companyId);
        $vendors->each(function (Vendor $v) use ($aggregates) {
            $agg              = $aggregates->get($v->id);
            $totalInvoiced    = (float) ($agg->total_invoiced ?? 0);
            $totalPaid        = (float) ($agg->total_paid     ?? 0);
            $v->totalInvoiced = $totalInvoiced;
            $v->balance       = (float) $v->opening_balance + $totalInvoiced - $totalPaid;
        });

        if ($filterOutstanding) {
            $vendors = $vendors->filter(fn ($v) => $v->balance > 0)->values();
        }

        $vendors = $vendors->sortByDesc(fn ($v) => $v->balance)->values();

        $totalPayable     = $vendors->sum(fn ($v) => max(0, $v->balance));
        $withBalanceCount = $vendors->filter(fn ($v) => $v->balance > 0)->count();
        $settledCount     = $vendors->filter(fn ($v) => $v->balance <= 0)->count();

        return view('accounting.vendors.index', compact(
            'vendors',
            'totalPayable',
            'filterOutstanding',
            'withBalanceCount',
            'settledCount',
        ));
    }

    // -------------------------------------------------------------------------

    public function create(): View
    {
        Gate::authorize('can-write');
        return view('accounting.vendors.create');
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
            'name.required' => 'اسم المورد مطلوب.',
            'email.email'   => 'البريد الإلكتروني غير صالح.',
        ]);

        $vendor = $this->service->createVendor($validated, $request->user()->company_id);

        $this->log->log(
            $request->user()->company_id,
            'created', 'vendor', $vendor->id,
            $vendor->name,
            "أنشأ مورداً جديداً [{$vendor->name}]."
        );

        return redirect()
            ->route('accounting.vendors.show', $vendor)
            ->with('success', "تم إنشاء المورد [{$vendor->name}] بنجاح.");
    }

    // -------------------------------------------------------------------------

    public function show(Request $request, Vendor $vendor): View
    {
        $this->authorizeCompany($request, $vendor);

        $vendor->load([
            'purchaseInvoices',
            'purchaseInvoices.payments',
            'purchasePayments.purchaseInvoice',
        ]);

        $balance       = $this->service->getBalance($vendor);
        $totalInvoiced = $this->service->getTotalInvoiced($vendor);
        $totalPaid     = $this->service->getTotalPaid($vendor);

        $unpaidInvoices  = $vendor->purchaseInvoices->filter(fn ($inv) => $inv->isUnpaid());
        $overdueInvoices = $vendor->purchaseInvoices->filter(fn ($inv) => $inv->isOverdue());
        $pendingInvoices = $unpaidInvoices;
        $allPayments     = $vendor->purchasePayments;

        return view('accounting.vendors.show', compact(
            'vendor',
            'balance',
            'totalInvoiced',
            'totalPaid',
            'unpaidInvoices',
            'overdueInvoices',
            'pendingInvoices',
            'allPayments',
        ));
    }

    // -------------------------------------------------------------------------

    public function storePayment(Request $request, Vendor $vendor): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $vendor);

        $validated = $request->validate([
            'amount'              => 'required|numeric|min:0.01',
            'payment_date'        => 'required|date',
            'payment_method'      => 'required|in:cash,bank,wallet,instapay,cheque,card,other',
            'purchase_invoice_id' => [
                'nullable',
                'integer',
                Rule::exists('purchase_invoices', 'id')->where('vendor_id', $vendor->id),
            ],
            'notes' => 'nullable|string|max:500',
        ], [
            'amount.required'              => 'المبلغ مطلوب.',
            'payment_date.required'        => 'تاريخ الدفع مطلوب.',
            'payment_method.required'      => 'طريقة الدفع مطلوبة.',
            'payment_method.in'            => 'طريقة الدفع غير صالحة.',
            'purchase_invoice_id.exists'   => 'الفاتورة المحددة غير موجودة.',
        ]);

        try {
            $result = $this->service->recordPayment($vendor, [
                'amount'         => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_date'   => $validated['payment_date'],
                'purchase_invoice_id' => $validated['purchase_invoice_id'] ?? null,
                'notes'          => $validated['notes'] ?? null,
            ]);
        } catch (\DomainException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        $payment = $result['payment'];

        $this->log->log(
            $request->user()->company_id,
            'created', 'purchase_payment', $payment->id,
            $vendor->name,
            "سجّل دفعة على حساب المورد [{$vendor->name}] بمبلغ {$validated['amount']}."
        );

        $message = 'تم تسجيل الدفعة بنجاح.';
        if (($result['openingAmount'] ?? 0) > 0) {
            $message .= ' تم تحميل ' . number_format($result['openingAmount'], 2) . ' على الرصيد الافتتاحي/حساب المورد.';
        }

        return back()->with('success', $message);
    }

    // -------------------------------------------------------------------------

    public function settle(Request $request, Vendor $vendor): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $vendor);

        $validated = $request->validate([
            'settlement_type' => 'required|in:discount,bad_debt',
        ], [
            'settlement_type.required' => 'نوع التسوية مطلوب.',
            'settlement_type.in'       => 'نوع التسوية غير صالح.',
        ]);

        $result = $this->service->settleAccount($vendor, $validated['settlement_type']);

        if ($result['invoiceCount'] === 0) {
            return back()->with('info', 'لا توجد فواتير مستحقة — الحساب مسوَّى بالفعل.');
        }

        $typeLabel = $validated['settlement_type'] === 'bad_debt' ? 'إعدام دين' : 'خصم';

        $this->log->log(
            $request->user()->company_id,
            'settled', 'vendor', $vendor->id,
            $vendor->name,
            "تسوية حساب [{$vendor->name}] ({$typeLabel}): {$result['invoiceCount']} فاتورة بإجمالي {$result['totalAmount']}."
        );

        return back()->with(
            'success',
            "تمت تسوية الحساب ({$typeLabel}) بنجاح. تم تسديد {$result['invoiceCount']} " .
            ($result['invoiceCount'] === 1 ? 'فاتورة' : 'فواتير') .
            " بإجمالي " . number_format($result['totalAmount'], 2) . " وتسجيل قيد محاسبي."
        );
    }

    // -------------------------------------------------------------------------

    private function authorizeCompany(Request $request, Vendor $vendor): void
    {
        abort_if($vendor->company_id !== $request->user()->company_id, 403);
    }
}

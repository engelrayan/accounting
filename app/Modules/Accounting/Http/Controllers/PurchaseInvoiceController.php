<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\PurchaseInvoice;
use App\Modules\Accounting\Models\PurchasePayment;
use App\Modules\Accounting\Models\Vendor;
use App\Modules\Accounting\Services\ActivityLogService;
use App\Modules\Accounting\Services\CompanySettingsService;
use App\Modules\Accounting\Services\PurchaseInvoiceService;
use App\Modules\Accounting\Services\Reports\ReportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PurchaseInvoiceController extends Controller
{
    public function __construct(
        private readonly PurchaseInvoiceService $service,
        private readonly ActivityLogService     $log,
        private readonly ReportExportService    $reportExport,
        private readonly CompanySettingsService $settingsService,
    ) {}

    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;
        $status    = $request->query('status');

        $invoices = PurchaseInvoice::forCompany($companyId)
            ->with('vendor:id,name,phone')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(25);

        $counts = PurchaseInvoice::forCompany($companyId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('accounting.purchase-invoices.index', compact('invoices', 'counts', 'status'));
    }

    // -------------------------------------------------------------------------

    public function create(Request $request): View
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        $vendors = Vendor::forCompany($companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $nextNumber       = $this->service->nextNumber($companyId);
        $preselectedVendor = (int) $request->query('vendor_id', 0);

        return view('accounting.purchase-invoices.create', compact('vendors', 'nextNumber', 'preselectedVendor'));
    }

    // -------------------------------------------------------------------------

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $validated = $request->validate([
            'vendor_id'             => ['required', 'integer', 'exists:vendors,id'],
            'issue_date'            => ['required', 'date'],
            'due_date'              => ['nullable', 'date', 'after_or_equal:issue_date'],
            'payment_method'        => ['nullable', 'string', 'in:cash,bank_transfer,cheque,card,other'],
            'vendor_invoice_number' => ['nullable', 'string', 'max:100'],
            'notes'                 => ['nullable', 'string', 'max:1000'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.description'   => ['required', 'string', 'max:500'],
            'items.*.quantity'      => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'    => ['required', 'numeric', 'min:0'],
        ], [
            'vendor_id.required'            => 'يرجى اختيار المورد.',
            'vendor_id.exists'              => 'المورد المحدد غير موجود.',
            'issue_date.required'           => 'تاريخ الإصدار مطلوب.',
            'due_date.after_or_equal'       => 'تاريخ الاستحقاق يجب أن يكون بعد تاريخ الإصدار أو يساويه.',
            'items.required'                => 'يجب إضافة بند واحد على الأقل.',
            'items.*.description.required'  => 'وصف البند مطلوب.',
            'items.*.quantity.required'     => 'الكمية مطلوبة.',
            'items.*.unit_price.required'   => 'سعر الوحدة مطلوب.',
        ]);

        $vendor = Vendor::where('id', $validated['vendor_id'])
            ->where('company_id', $request->user()->company_id)
            ->firstOrFail();

        try {
            $invoice = $this->service->create($validated, $request->user()->company_id);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['invoice' => $e->getMessage()]);
        }

        $this->log->log(
            $request->user()->company_id,
            'created', 'purchase_invoice', $invoice->id,
            $invoice->invoice_number,
            "أنشأ فاتورة مشتريات [{$invoice->invoice_number}] من المورد [{$vendor->name}] بمبلغ {$invoice->amount}."
        );

        return redirect()
            ->route('accounting.purchase-invoices.show', $invoice)
            ->with('success', "تم إنشاء الفاتورة [{$invoice->invoice_number}] بنجاح.");
    }

    // -------------------------------------------------------------------------

    public function show(Request $request, PurchaseInvoice $purchaseInvoice): View
    {
        abort_if($purchaseInvoice->company_id !== $request->user()->company_id, 403);

        $purchaseInvoice->load('vendor', 'items');

        $payments = PurchasePayment::where('purchase_invoice_id', $purchaseInvoice->id)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        return view('accounting.purchase-invoices.show', compact('purchaseInvoice', 'payments'));
    }

    // -------------------------------------------------------------------------

    public function downloadPdf(Request $request, PurchaseInvoice $purchaseInvoice): Response
    {
        abort_if($purchaseInvoice->company_id !== $request->user()->company_id, 403);

        $purchaseInvoice->load('vendor', 'items');

        $settings = $this->settingsService->forCompany($purchaseInvoice->company_id);
        $purchaseInvoice->_pdfSettings = $settings;

        return $this->reportExport->downloadPdf(
            view: 'accounting.pdf.purchase_invoice',
            data: compact('purchaseInvoice'),
            filename: 'purchase-invoice-' . $purchaseInvoice->invoice_number,
        );
    }

    // -------------------------------------------------------------------------

    public function recordPayment(Request $request, PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        Gate::authorize('can-write');
        abort_if($purchaseInvoice->company_id !== $request->user()->company_id, 403);

        $validated = $request->validate([
            'payment_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'in:cash,bank,wallet,instapay,cheque,card,other'],
            'payment_date'   => ['nullable', 'date'],
            'payment_notes'  => ['nullable', 'string', 'max:500'],
        ], [
            'payment_amount.required' => 'مبلغ الدفعة مطلوب.',
            'payment_amount.min'      => 'يجب أن يكون المبلغ أكبر من صفر.',
        ]);

        try {
            $payment = $this->service->recordPayment($purchaseInvoice, [
                'amount'         => $validated['payment_amount'],
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'payment_date'   => $validated['payment_date']   ?? now()->toDateString(),
                'notes'          => $validated['payment_notes']  ?? null,
            ]);
        } catch (\DomainException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        $this->log->log(
            $request->user()->company_id,
            'updated', 'purchase_invoice', $purchaseInvoice->id,
            $purchaseInvoice->invoice_number,
            "سجَّل دفعة [{$payment->id}] بمبلغ {$payment->amount} على الفاتورة [{$purchaseInvoice->invoice_number}]."
        );

        return back()->with('success', 'تم تسجيل الدفعة بنجاح.');
    }
}

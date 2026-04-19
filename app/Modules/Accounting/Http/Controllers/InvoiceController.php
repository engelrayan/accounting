<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Customer;
use App\Modules\Accounting\Models\Invoice;
use App\Modules\Accounting\Models\Payment;
use App\Modules\Accounting\Services\ActivityLogService;
use App\Modules\Accounting\Services\CompanySettingsService;
use App\Modules\Accounting\Services\InvoiceService;
use App\Modules\Accounting\Services\Reports\ReportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService         $service,
        private readonly ActivityLogService     $log,
        private readonly ReportExportService    $reportExport,
        private readonly CompanySettingsService $settingsService,
    ) {}

    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;
        $status    = $request->query('status');

        $invoices = Invoice::forCompany($companyId)
            ->with('customer:id,name,phone')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(25);

        $counts = Invoice::forCompany($companyId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('accounting.invoices.index', compact('invoices', 'counts', 'status'));
    }

    // -------------------------------------------------------------------------

    public function create(Request $request): View
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        $customers = Customer::forCompany($companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $nextNumber         = $this->service->nextNumber($companyId);
        $preselectedCustomer = (int) $request->query('customer_id', 0);

        return view('accounting.invoices.create', compact('customers', 'nextNumber', 'preselectedCustomer'));
    }

    // -------------------------------------------------------------------------

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $validated = $request->validate([
            'customer_id'    => ['required', 'integer', 'exists:customers,id'],
            'issue_date'     => ['required', 'date'],
            'due_date'       => ['nullable', 'date', 'after_or_equal:issue_date'],
            'payment_method' => ['nullable', 'string', 'in:cash,bank_transfer,cheque,card,other'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0'],
        ], [
            'customer_id.required'        => 'يرجى اختيار العميل.',
            'customer_id.exists'          => 'العميل المحدد غير موجود.',
            'issue_date.required'         => 'تاريخ الإصدار مطلوب.',
            'due_date.after_or_equal'     => 'تاريخ الاستحقاق يجب أن يكون بعد تاريخ الإصدار أو يساويه.',
            'items.required'              => 'يجب إضافة بند واحد على الأقل.',
            'items.*.description.required'=> 'وصف البند مطلوب.',
            'items.*.quantity.required'   => 'الكمية مطلوبة.',
            'items.*.unit_price.required' => 'سعر الوحدة مطلوب.',
        ]);

        // Ensure customer belongs to this company
        $customer = Customer::where('id', $validated['customer_id'])
            ->where('company_id', $request->user()->company_id)
            ->firstOrFail();

        try {
            $invoice = $this->service->create($validated, $request->user()->company_id);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['invoice' => $e->getMessage()]);
        }

        $this->log->log(
            $request->user()->company_id,
            'created', 'invoice', $invoice->id,
            $invoice->invoice_number,
            "أنشأ فاتورة [{$invoice->invoice_number}] للعميل [{$customer->name}] بمبلغ {$invoice->amount}."
        );

        return redirect()
            ->route('accounting.invoices.show', $invoice)
            ->with('success', "تم إنشاء الفاتورة [{$invoice->invoice_number}] بنجاح.");
    }

    // -------------------------------------------------------------------------

    public function show(Request $request, Invoice $invoice): View
    {
        abort_if($invoice->company_id !== $request->user()->company_id, 403);

        $invoice->load('customer', 'items', 'creditNotes');

        $payments = Payment::where('invoice_id', $invoice->id)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        return view('accounting.invoices.show', compact('invoice', 'payments'));
    }

    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------

    public function downloadPdf(Request $request, Invoice $invoice): Response
    {
        abort_if($invoice->company_id !== $request->user()->company_id, 403);

        $invoice->load('customer', 'items');

        $settings = $this->settingsService->forCompany($invoice->company_id);
        $invoice->_pdfSettings = $settings;

        return $this->reportExport->downloadPdf(
            view: 'accounting.pdf.invoice',
            data: compact('invoice'),
            filename: 'invoice-' . $invoice->invoice_number,
        );
    }

    // -------------------------------------------------------------------------

    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        Gate::authorize('can-write');
        abort_if($invoice->company_id !== $request->user()->company_id, 403);

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
            $payment = $this->service->recordPayment($invoice, [
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
            'updated', 'invoice', $invoice->id,
            $invoice->invoice_number,
            "سجَّل دفعة [{$payment->id}] بمبلغ {$payment->amount} على الفاتورة [{$invoice->invoice_number}]."
        );

        return back()->with('success', 'تم تسجيل الدفعة بنجاح.');
    }
}

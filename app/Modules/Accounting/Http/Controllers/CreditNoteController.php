<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\CreditNote;
use App\Modules\Accounting\Models\Invoice;
use App\Modules\Accounting\Services\ActivityLogService;
use App\Modules\Accounting\Services\CompanySettingsService;
use App\Modules\Accounting\Services\CreditNoteService;
use App\Modules\Accounting\Services\Reports\ReportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CreditNoteController extends Controller
{
    public function __construct(
        private readonly CreditNoteService      $service,
        private readonly ActivityLogService     $log,
        private readonly ReportExportService    $reportExport,
        private readonly CompanySettingsService $settingsService,
    ) {}

    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $creditNotes = CreditNote::forCompany($companyId)
            ->with(['customer:id,name', 'invoice:id,invoice_number'])
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('accounting.credit-notes.index', compact('creditNotes'));
    }

    // -------------------------------------------------------------------------

    public function create(Request $request): View
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        // Can be pre-seeded from the invoice show page via ?invoice_id=X
        $preselectedInvoice = null;
        if ($invoiceId = $request->query('invoice_id')) {
            $preselectedInvoice = Invoice::where('id', $invoiceId)
                ->where('company_id', $companyId)
                ->with('customer:id,name')
                ->first();
        }

        // Eligible invoices: pending or partial, belonging to this company
        $invoices = Invoice::forCompany($companyId)
            ->whereIn('status', ['pending', 'partial'])
            ->with('customer:id,name')
            ->orderByDesc('issue_date')
            ->get(['id', 'invoice_number', 'customer_id', 'amount', 'remaining_amount', 'tax_rate']);

        $nextNumber = $this->service->nextNumber($companyId);

        return view('accounting.credit-notes.create', compact(
            'invoices', 'preselectedInvoice', 'nextNumber'
        ));
    }

    // -------------------------------------------------------------------------

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'amount'     => ['required', 'numeric', 'min:0.01'],
            'reason'     => ['nullable', 'string', 'max:1000'],
            'issue_date' => ['required', 'date'],
        ], [
            'invoice_id.required' => 'يرجى اختيار الفاتورة.',
            'invoice_id.exists'   => 'الفاتورة المحددة غير موجودة.',
            'amount.required'     => 'يرجى إدخال المبلغ.',
            'amount.min'          => 'يجب أن يكون المبلغ أكبر من صفر.',
            'issue_date.required' => 'تاريخ الإشعار مطلوب.',
        ]);

        $invoice = Invoice::where('id', $validated['invoice_id'])
            ->where('company_id', $companyId)
            ->firstOrFail();

        try {
            $creditNote = $this->service->create($invoice, [
                'amount'     => $validated['amount'],
                'reason'     => $validated['reason'] ?? null,
                'issue_date' => $validated['issue_date'],
            ]);
        } catch (\DomainException|\RuntimeException $e) {
            return back()->withInput()->withErrors(['credit_note' => $e->getMessage()]);
        }

        $this->log->log(
            $companyId,
            'created', 'credit_note', $creditNote->id,
            $creditNote->credit_note_number,
            "أنشأ إشعار دائن [{$creditNote->credit_note_number}] على الفاتورة [{$invoice->invoice_number}] بمبلغ {$creditNote->total}."
        );

        return redirect()
            ->route('accounting.credit-notes.show', $creditNote)
            ->with('success', "تم إصدار الإشعار الدائن [{$creditNote->credit_note_number}] بنجاح.");
    }

    // -------------------------------------------------------------------------

    public function show(Request $request, CreditNote $creditNote): View
    {
        abort_if($creditNote->company_id !== $request->user()->company_id, 403);

        $creditNote->load(['customer', 'invoice.items']);

        return view('accounting.credit-notes.show', compact('creditNote'));
    }

    // -------------------------------------------------------------------------
    // Shortcut: POST from invoice show page
    // -------------------------------------------------------------------------

    public function downloadPdf(Request $request, CreditNote $creditNote): Response
    {
        abort_if($creditNote->company_id !== $request->user()->company_id, 403);

        $creditNote->load(['customer', 'invoice.items']);

        $settings = $this->settingsService->forCompany($creditNote->company_id);
        $creditNote->_pdfSettings = $settings;

        return $this->reportExport->downloadPdf(
            view: 'accounting.pdf.credit_note',
            data: compact('creditNote'),
            filename: 'credit-note-' . $creditNote->credit_note_number,
        );
    }

    // -------------------------------------------------------------------------

    public function storeForInvoice(Request $request, Invoice $invoice): RedirectResponse
    {
        Gate::authorize('can-write');
        abort_if($invoice->company_id !== $request->user()->company_id, 403);

        $validated = $request->validate([
            'amount'     => ['required', 'numeric', 'min:0.01'],
            'reason'     => ['nullable', 'string', 'max:1000'],
            'issue_date' => ['required', 'date'],
        ], [
            'amount.required'     => 'يرجى إدخال المبلغ.',
            'amount.min'          => 'يجب أن يكون المبلغ أكبر من صفر.',
            'issue_date.required' => 'تاريخ الإشعار مطلوب.',
        ]);

        try {
            $creditNote = $this->service->create($invoice, $validated);
        } catch (\DomainException|\RuntimeException $e) {
            return back()->withErrors(['credit_note' => $e->getMessage()]);
        }

        $this->log->log(
            $request->user()->company_id,
            'created', 'credit_note', $creditNote->id,
            $creditNote->credit_note_number,
            "أنشأ إشعار دائن [{$creditNote->credit_note_number}] على الفاتورة [{$invoice->invoice_number}] بمبلغ {$creditNote->total}."
        );

        return redirect()
            ->route('accounting.credit-notes.show', $creditNote)
            ->with('success', "تم إصدار الإشعار الدائن [{$creditNote->credit_note_number}] بنجاح.");
    }
}

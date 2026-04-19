<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Customer;
use App\Modules\Accounting\Models\Invoice;
use App\Modules\Accounting\Models\InvoiceItem;
use App\Modules\Accounting\Models\Product;
use App\Modules\Accounting\Models\Quotation;
use App\Modules\Accounting\Models\QuotationItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuotationController extends Controller
{
    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;
        $status    = $request->query('status');
        $customerId = $request->query('customer_id');

        $quotations = Quotation::forCompany($companyId)
            ->with('customer:id,name,phone')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25);

        // Stats
        $stats = Quotation::forCompany($companyId)
            ->selectRaw('status, count(*) as count, sum(total) as total_value')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $draftCount    = (int) ($stats->get('draft')?->count ?? 0);
        $sentCount     = (int) ($stats->get('sent')?->count ?? 0);
        $acceptedCount = (int) ($stats->get('accepted')?->count ?? 0);

        $totalValue = Quotation::forCompany($companyId)
            ->whereIn('status', ['draft', 'sent', 'accepted'])
            ->sum('total');

        $customers = Customer::forCompany($companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('accounting.quotations.index', compact(
            'quotations',
            'stats',
            'draftCount',
            'sentCount',
            'acceptedCount',
            'totalValue',
            'customers',
            'status',
            'customerId',
        ));
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function create(Request $request): View
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        $customers = Customer::forCompany($companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $products = Product::forCompany($companyId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'sale_price', 'tax_rate', 'unit']);

        $nextNumber         = $this->generateNextNumber($companyId);
        $preselectedCustomer = (int) $request->query('customer_id', 0);

        return view('accounting.quotations.create', compact(
            'customers',
            'products',
            'nextNumber',
            'preselectedCustomer',
        ));
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $validated = $request->validate([
            'customer_id'   => ['required', 'integer', 'exists:customers,id'],
            'date'          => ['required', 'date'],
            'valid_until'   => ['nullable', 'date', 'after_or_equal:date'],
            'notes'         => ['nullable', 'string', 'max:2000'],
            'terms'         => ['nullable', 'string', 'max:2000'],
            'items'         => ['required', 'array', 'min:1'],
            'items.*.description'     => ['required', 'string', 'max:500'],
            'items.*.quantity'        => ['required', 'numeric', 'min:0.001'],
            'items.*.unit'            => ['nullable', 'string', 'max:30'],
            'items.*.unit_price'      => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_rate'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.product_id'      => ['nullable', 'integer', 'exists:products,id'],
        ], [
            'customer_id.required'            => 'يرجى اختيار العميل.',
            'customer_id.exists'              => 'العميل المحدد غير موجود.',
            'date.required'                   => 'تاريخ عرض السعر مطلوب.',
            'valid_until.after_or_equal'      => 'تاريخ الصلاحية يجب أن يكون بعد تاريخ عرض السعر أو يساويه.',
            'items.required'                  => 'يجب إضافة بند واحد على الأقل.',
            'items.*.description.required'    => 'وصف البند مطلوب.',
            'items.*.quantity.required'       => 'الكمية مطلوبة.',
            'items.*.unit_price.required'     => 'سعر الوحدة مطلوب.',
        ]);

        $companyId = $request->user()->company_id;

        // Ensure customer belongs to this company
        Customer::where('id', $validated['customer_id'])
            ->where('company_id', $companyId)
            ->firstOrFail();

        DB::transaction(function () use ($validated, $companyId, $request, &$quotation) {
            [$subtotal, $taxAmount, $discountAmount, $total, $itemsData] =
                $this->calculateTotals($validated['items']);

            $quotation = Quotation::create([
                'company_id'      => $companyId,
                'customer_id'     => $validated['customer_id'],
                'quotation_number'=> $this->generateNextNumber($companyId),
                'date'            => $validated['date'],
                'valid_until'     => $validated['valid_until'] ?? null,
                'status'          => 'draft',
                'notes'           => $validated['notes'] ?? null,
                'terms'           => $validated['terms'] ?? null,
                'subtotal'        => $subtotal,
                'tax_amount'      => $taxAmount,
                'discount_amount' => $discountAmount,
                'total'           => $total,
                'created_by'      => $request->user()->id,
            ]);

            foreach ($itemsData as $sortOrder => $itemData) {
                QuotationItem::create(array_merge($itemData, [
                    'quotation_id' => $quotation->id,
                    'sort_order'   => $sortOrder,
                ]));
            }
        });

        return redirect()
            ->route('accounting.quotations.show', $quotation)
            ->with('success', "تم إنشاء عرض السعر [{$quotation->quotation_number}] بنجاح.");
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(Request $request, Quotation $quotation): View
    {
        abort_if($quotation->company_id !== $request->user()->company_id, 403);

        $quotation->load('customer', 'items.product', 'invoice');

        return view('accounting.quotations.show', compact('quotation'));
    }

    // -------------------------------------------------------------------------
    // Edit
    // -------------------------------------------------------------------------

    public function edit(Request $request, Quotation $quotation): View
    {
        Gate::authorize('can-write');
        abort_if($quotation->company_id !== $request->user()->company_id, 403);
        abort_if(! $quotation->isDraft(), 403, 'يمكن تعديل عروض الأسعار في حالة المسودة فقط.');

        $companyId = $request->user()->company_id;

        $customers = Customer::forCompany($companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $products = Product::forCompany($companyId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'sale_price', 'tax_rate', 'unit']);

        $quotation->load('items');

        return view('accounting.quotations.edit', compact('quotation', 'customers', 'products'));
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, Quotation $quotation): RedirectResponse
    {
        Gate::authorize('can-write');
        abort_if($quotation->company_id !== $request->user()->company_id, 403);
        abort_if(! $quotation->isDraft(), 403, 'يمكن تعديل عروض الأسعار في حالة المسودة فقط.');

        $validated = $request->validate([
            'customer_id'             => ['required', 'integer', 'exists:customers,id'],
            'date'                    => ['required', 'date'],
            'valid_until'             => ['nullable', 'date', 'after_or_equal:date'],
            'notes'                   => ['nullable', 'string', 'max:2000'],
            'terms'                   => ['nullable', 'string', 'max:2000'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.description'     => ['required', 'string', 'max:500'],
            'items.*.quantity'        => ['required', 'numeric', 'min:0.001'],
            'items.*.unit'            => ['nullable', 'string', 'max:30'],
            'items.*.unit_price'      => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_rate'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.product_id'      => ['nullable', 'integer', 'exists:products,id'],
        ], [
            'customer_id.required'            => 'يرجى اختيار العميل.',
            'customer_id.exists'              => 'العميل المحدد غير موجود.',
            'date.required'                   => 'تاريخ عرض السعر مطلوب.',
            'valid_until.after_or_equal'      => 'تاريخ الصلاحية يجب أن يكون بعد تاريخ عرض السعر أو يساويه.',
            'items.required'                  => 'يجب إضافة بند واحد على الأقل.',
            'items.*.description.required'    => 'وصف البند مطلوب.',
            'items.*.quantity.required'       => 'الكمية مطلوبة.',
            'items.*.unit_price.required'     => 'سعر الوحدة مطلوب.',
        ]);

        $companyId = $request->user()->company_id;

        Customer::where('id', $validated['customer_id'])
            ->where('company_id', $companyId)
            ->firstOrFail();

        DB::transaction(function () use ($validated, $quotation) {
            [$subtotal, $taxAmount, $discountAmount, $total, $itemsData] =
                $this->calculateTotals($validated['items']);

            $quotation->update([
                'customer_id'     => $validated['customer_id'],
                'date'            => $validated['date'],
                'valid_until'     => $validated['valid_until'] ?? null,
                'notes'           => $validated['notes'] ?? null,
                'terms'           => $validated['terms'] ?? null,
                'subtotal'        => $subtotal,
                'tax_amount'      => $taxAmount,
                'discount_amount' => $discountAmount,
                'total'           => $total,
            ]);

            $quotation->items()->delete();

            foreach ($itemsData as $sortOrder => $itemData) {
                QuotationItem::create(array_merge($itemData, [
                    'quotation_id' => $quotation->id,
                    'sort_order'   => $sortOrder,
                ]));
            }
        });

        return redirect()
            ->route('accounting.quotations.show', $quotation)
            ->with('success', "تم تحديث عرض السعر [{$quotation->quotation_number}] بنجاح.");
    }

    // -------------------------------------------------------------------------
    // Send (draft → sent)
    // -------------------------------------------------------------------------

    public function send(Request $request, Quotation $quotation): RedirectResponse
    {
        Gate::authorize('can-write');
        abort_if($quotation->company_id !== $request->user()->company_id, 403);
        abort_if(! $quotation->isDraft(), 422, 'يمكن إرسال عروض الأسعار في حالة المسودة فقط.');

        $quotation->update(['status' => 'sent']);

        return back()->with('success', "تم تغيير حالة عرض السعر [{$quotation->quotation_number}] إلى مرسل.");
    }

    // -------------------------------------------------------------------------
    // Accept (sent → accepted)
    // -------------------------------------------------------------------------

    public function accept(Request $request, Quotation $quotation): RedirectResponse
    {
        Gate::authorize('can-write');
        abort_if($quotation->company_id !== $request->user()->company_id, 403);
        abort_if(! $quotation->isSent(), 422, 'يمكن قبول عروض الأسعار المرسلة فقط.');

        $quotation->update(['status' => 'accepted']);

        return back()->with('success', "تم قبول عرض السعر [{$quotation->quotation_number}].");
    }

    // -------------------------------------------------------------------------
    // Reject (sent/accepted → rejected)
    // -------------------------------------------------------------------------

    public function reject(Request $request, Quotation $quotation): RedirectResponse
    {
        Gate::authorize('can-write');
        abort_if($quotation->company_id !== $request->user()->company_id, 403);
        abort_if(
            ! in_array($quotation->status, ['sent', 'accepted']),
            422,
            'يمكن رفض عروض الأسعار المرسلة أو المقبولة فقط.'
        );

        $quotation->update(['status' => 'rejected']);

        return back()->with('success', "تم رفض عرض السعر [{$quotation->quotation_number}].");
    }

    // -------------------------------------------------------------------------
    // Convert to Invoice
    // -------------------------------------------------------------------------

    public function convertToInvoice(Request $request, Quotation $quotation): RedirectResponse
    {
        Gate::authorize('can-write');
        abort_if($quotation->company_id !== $request->user()->company_id, 403);
        abort_if(! $quotation->canConvert(), 422, 'يمكن تحويل عروض الأسعار المرسلة أو المقبولة فقط إلى فواتير.');

        $quotation->load('items');

        $invoice = DB::transaction(function () use ($quotation, $request) {
            // Generate invoice number by querying the existing invoices max number
            $year          = now()->year;
            $prefix        = "INV-{$year}-";
            $lastNumber    = Invoice::forCompany($quotation->company_id)
                ->where('invoice_number', 'like', "{$prefix}%")
                ->max('invoice_number');

            if ($lastNumber) {
                $seq = (int) substr($lastNumber, strlen($prefix)) + 1;
            } else {
                $seq = 1;
            }

            $invoiceNumber = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            // Compute tax_rate from quotation (use first item's rate or derive from totals)
            $taxRate = 0;
            if ((float) $quotation->subtotal > 0 && (float) $quotation->tax_amount > 0) {
                $taxRate = round((float) $quotation->tax_amount / (float) $quotation->subtotal * 100, 2);
            }

            $invoice = Invoice::create([
                'company_id'       => $quotation->company_id,
                'customer_id'      => $quotation->customer_id,
                'invoice_number'   => $invoiceNumber,
                'issue_date'       => now()->toDateString(),
                'due_date'         => null,
                'subtotal'         => $quotation->subtotal,
                'tax_rate'         => $taxRate,
                'tax_amount'       => $quotation->tax_amount,
                'amount'           => $quotation->total,
                'paid_amount'      => 0,
                'credited_amount'  => 0,
                'remaining_amount' => $quotation->total,
                'status'           => 'pending',
                'notes'            => $quotation->notes,
                'payment_method'   => null,
            ]);

            foreach ($quotation->items as $qItem) {
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'product_id'  => $qItem->product_id,
                    'description' => $qItem->description,
                    'quantity'    => $qItem->quantity,
                    'unit_price'  => $qItem->unit_price,
                    'total'       => $qItem->total,
                ]);
            }

            $quotation->update([
                'status'     => 'invoiced',
                'invoice_id' => $invoice->id,
            ]);

            return $invoice;
        });

        return redirect()
            ->route('accounting.invoices.show', $invoice)
            ->with('success', "تم تحويل عرض السعر [{$quotation->quotation_number}] إلى فاتورة [{$invoice->invoice_number}] بنجاح.");
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Generate the next quotation number for a company in the current year.
     * Format: QT-{YEAR}-{NNNN}
     */
    private function generateNextNumber(int $companyId): string
    {
        $year   = now()->year;
        $prefix = "QT-{$year}-";

        $last = Quotation::where('company_id', $companyId)
            ->where('quotation_number', 'like', "{$prefix}%")
            ->max('quotation_number');

        if ($last) {
            $seq = (int) substr($last, strlen($prefix)) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate line-item and overall totals from the raw items array.
     *
     * Returns [subtotal, taxAmount, discountAmount, total, itemsData[]]
     */
    private function calculateTotals(array $items): array
    {
        $subtotal       = 0.0;
        $taxAmount      = 0.0;
        $discountAmount = 0.0;
        $itemsData      = [];

        foreach ($items as $item) {
            $qty          = (float) ($item['quantity']      ?? 1);
            $unitPrice    = (float) ($item['unit_price']    ?? 0);
            $taxRate      = (float) ($item['tax_rate']      ?? 0);
            $discountRate = (float) ($item['discount_rate'] ?? 0);

            $lineBase     = $qty * $unitPrice;
            $lineDiscount = round($lineBase * $discountRate / 100, 2);
            $lineAfterDiscount = $lineBase - $lineDiscount;
            $lineTax      = round($lineAfterDiscount * $taxRate / 100, 2);
            $lineTotal    = round($lineAfterDiscount + $lineTax, 2);

            $subtotal       += $lineBase;
            $discountAmount += $lineDiscount;
            $taxAmount      += $lineTax;

            $itemsData[] = [
                'product_id'      => $item['product_id'] ?? null,
                'description'     => $item['description'],
                'quantity'        => $qty,
                'unit'            => $item['unit'] ?? null,
                'unit_price'      => $unitPrice,
                'tax_rate'        => $taxRate,
                'tax_amount'      => $lineTax,
                'discount_rate'   => $discountRate,
                'discount_amount' => $lineDiscount,
                'total'           => $lineTotal,
            ];
        }

        $total = round($subtotal - $discountAmount + $taxAmount, 2);

        return [
            round($subtotal, 2),
            round($taxAmount, 2),
            round($discountAmount, 2),
            $total,
            $itemsData,
        ];
    }
}

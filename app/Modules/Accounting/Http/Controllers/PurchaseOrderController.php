<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Product;
use App\Modules\Accounting\Models\PurchaseInvoice;
use App\Modules\Accounting\Models\PurchaseInvoiceItem;
use App\Modules\Accounting\Models\PurchaseOrder;
use App\Modules\Accounting\Models\PurchaseOrderItem;
use App\Modules\Accounting\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;
        $status    = $request->query('status');
        $vendorId  = $request->query('vendor_id');

        $purchaseOrders = PurchaseOrder::forCompany($companyId)
            ->with('vendor:id,name')
            ->when($status,   fn ($q) => $q->where('status', $status))
            ->when($vendorId, fn ($q) => $q->where('vendor_id', $vendorId))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $counts = PurchaseOrder::forCompany($companyId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $draftCount    = (int) ($counts['draft']    ?? 0);
        $sentCount     = (int) ($counts['sent']     ?? 0);
        $receivedCount = (int) ($counts['received'] ?? 0);

        $totalValue = PurchaseOrder::forCompany($companyId)->sum('total');

        $vendors = Vendor::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('accounting.purchase-orders.index', compact(
            'purchaseOrders', 'counts', 'status', 'vendorId', 'vendors',
            'draftCount', 'sentCount', 'receivedCount', 'totalValue'
        ));
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function create(Request $request): View
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        $vendors = Vendor::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::forCompany($companyId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'purchase_price', 'tax_rate']);

        return view('accounting.purchase-orders.create', compact('vendors', 'products'));
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'vendor_id'              => ['required', 'integer', 'exists:vendors,id'],
            'date'                   => ['required', 'date'],
            'expected_date'          => ['nullable', 'date'],
            'notes'                  => ['nullable', 'string', 'max:2000'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.description'    => ['required', 'string', 'max:500'],
            'items.*.product_id'     => ['nullable', 'integer', 'exists:products,id'],
            'items.*.quantity'       => ['required', 'numeric', 'min:0.001'],
            'items.*.unit'           => ['nullable', 'string', 'max:30'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $poNumber = $this->generatePoNumber($companyId);

        DB::transaction(function () use ($data, $companyId, $poNumber, $request) {
            $subtotal  = 0;
            $taxAmount = 0;

            $itemsData = [];
            foreach ($data['items'] as $index => $item) {
                $qty      = (float) $item['quantity'];
                $price    = (float) $item['unit_price'];
                $taxRate  = (float) ($item['tax_rate'] ?? 0);
                $lineNet  = round($qty * $price, 2);
                $lineTax  = round($lineNet * $taxRate / 100, 2);
                $lineTotal = $lineNet + $lineTax;

                $subtotal  += $lineNet;
                $taxAmount += $lineTax;

                $itemsData[] = [
                    'product_id'  => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity'    => $qty,
                    'unit'        => $item['unit'] ?? null,
                    'unit_price'  => $price,
                    'tax_rate'    => $taxRate,
                    'tax_amount'  => $lineTax,
                    'total'       => $lineTotal,
                    'sort_order'  => $index,
                ];
            }

            $order = PurchaseOrder::create([
                'company_id'    => $companyId,
                'vendor_id'     => $data['vendor_id'],
                'po_number'     => $poNumber,
                'date'          => $data['date'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes'         => $data['notes'] ?? null,
                'subtotal'      => round($subtotal, 2),
                'tax_amount'    => round($taxAmount, 2),
                'total'         => round($subtotal + $taxAmount, 2),
                'status'        => 'draft',
                'created_by'    => $request->user()->id,
            ]);

            foreach ($itemsData as $itemRow) {
                $order->items()->create($itemRow);
            }
        });

        return redirect()->route('accounting.purchase-orders.index')
            ->with('success', "تم إنشاء أمر الشراء {$poNumber} بنجاح.");
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(Request $request, PurchaseOrder $purchaseOrder): View
    {
        $this->authorizeCompany($request, $purchaseOrder);

        $purchaseOrder->load(['vendor', 'items.product', 'purchaseInvoice']);

        return view('accounting.purchase-orders.show', compact('purchaseOrder'));
    }

    // -------------------------------------------------------------------------
    // Edit
    // -------------------------------------------------------------------------

    public function edit(Request $request, PurchaseOrder $purchaseOrder): View
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $purchaseOrder);

        abort_unless($purchaseOrder->isDraft(), 403, 'يمكن تعديل أوامر الشراء بحالة مسودة فقط.');

        $companyId = $request->user()->company_id;

        $vendors = Vendor::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::forCompany($companyId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'purchase_price', 'tax_rate']);

        $purchaseOrder->load('items.product');

        return view('accounting.purchase-orders.edit', compact('purchaseOrder', 'vendors', 'products'));
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $purchaseOrder);

        abort_unless($purchaseOrder->isDraft(), 403, 'يمكن تعديل أوامر الشراء بحالة مسودة فقط.');

        $data = $request->validate([
            'vendor_id'              => ['required', 'integer', 'exists:vendors,id'],
            'date'                   => ['required', 'date'],
            'expected_date'          => ['nullable', 'date'],
            'notes'                  => ['nullable', 'string', 'max:2000'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.description'    => ['required', 'string', 'max:500'],
            'items.*.product_id'     => ['nullable', 'integer', 'exists:products,id'],
            'items.*.quantity'       => ['required', 'numeric', 'min:0.001'],
            'items.*.unit'           => ['nullable', 'string', 'max:30'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        DB::transaction(function () use ($data, $purchaseOrder) {
            $subtotal  = 0;
            $taxAmount = 0;

            $itemsData = [];
            foreach ($data['items'] as $index => $item) {
                $qty       = (float) $item['quantity'];
                $price     = (float) $item['unit_price'];
                $taxRate   = (float) ($item['tax_rate'] ?? 0);
                $lineNet   = round($qty * $price, 2);
                $lineTax   = round($lineNet * $taxRate / 100, 2);
                $lineTotal = $lineNet + $lineTax;

                $subtotal  += $lineNet;
                $taxAmount += $lineTax;

                $itemsData[] = [
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id'        => $item['product_id'] ?? null,
                    'description'       => $item['description'],
                    'quantity'          => $qty,
                    'unit'              => $item['unit'] ?? null,
                    'unit_price'        => $price,
                    'tax_rate'          => $taxRate,
                    'tax_amount'        => $lineTax,
                    'total'             => $lineTotal,
                    'sort_order'        => $index,
                ];
            }

            $purchaseOrder->items()->delete();

            $purchaseOrder->update([
                'vendor_id'     => $data['vendor_id'],
                'date'          => $data['date'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes'         => $data['notes'] ?? null,
                'subtotal'      => round($subtotal, 2),
                'tax_amount'    => round($taxAmount, 2),
                'total'         => round($subtotal + $taxAmount, 2),
            ]);

            PurchaseOrderItem::insert($itemsData);
        });

        return redirect()->route('accounting.purchase-orders.show', $purchaseOrder)
            ->with('success', 'تم تحديث أمر الشراء بنجاح.');
    }

    // -------------------------------------------------------------------------
    // Send  (draft → sent)
    // -------------------------------------------------------------------------

    public function send(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $purchaseOrder);

        abort_unless($purchaseOrder->isDraft(), 422, 'يمكن إرسال أوامر الشراء بحالة مسودة فقط.');

        $purchaseOrder->update(['status' => 'sent']);

        return back()->with('success', 'تم تغيير حالة أمر الشراء إلى "مرسل للمورد".');
    }

    // -------------------------------------------------------------------------
    // Receive  (sent → received)
    // -------------------------------------------------------------------------

    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $purchaseOrder);

        abort_unless($purchaseOrder->isSent(), 422, 'يمكن تأكيد الاستلام لأوامر الشراء المرسلة فقط.');

        $purchaseOrder->update(['status' => 'received']);

        return back()->with('success', 'تم تغيير حالة أمر الشراء إلى "تم الاستلام".');
    }

    // -------------------------------------------------------------------------
    // Cancel  (draft|sent → cancelled)
    // -------------------------------------------------------------------------

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $purchaseOrder);

        abort_unless(
            $purchaseOrder->isDraft() || $purchaseOrder->isSent(),
            422,
            'يمكن إلغاء أوامر الشراء بحالة مسودة أو مرسل فقط.'
        );

        $purchaseOrder->update(['status' => 'cancelled']);

        return back()->with('success', 'تم إلغاء أمر الشراء بنجاح.');
    }

    // -------------------------------------------------------------------------
    // Convert to Purchase Invoice
    // -------------------------------------------------------------------------

    public function convertToInvoice(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $purchaseOrder);

        abort_unless($purchaseOrder->canConvert(), 422, 'يمكن تحويل أوامر الشراء المرسلة أو المستلمة فقط.');

        $invoice = DB::transaction(function () use ($purchaseOrder) {
            $purchaseOrder->load('items');

            // Create the purchase invoice header
            $invoice = PurchaseInvoice::create([
                'company_id'  => $purchaseOrder->company_id,
                'vendor_id'   => $purchaseOrder->vendor_id,
                'subtotal'    => $purchaseOrder->subtotal,
                'tax_amount'  => $purchaseOrder->tax_amount,
                'amount'      => $purchaseOrder->total,
                'paid_amount' => 0,
                'issue_date'  => now()->toDateString(),
                'notes'       => $purchaseOrder->notes,
            ]);

            // Create invoice lines from PO items
            foreach ($purchaseOrder->items as $item) {
                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id'          => $item->product_id,
                    'description'         => $item->description,
                    'quantity'            => $item->quantity,
                    'unit_price'          => $item->unit_price,
                    'total'               => $item->total,
                ]);
            }

            // Link the PO to the new invoice and mark it as invoiced
            $purchaseOrder->update([
                'purchase_invoice_id' => $invoice->id,
                'status'              => 'invoiced',
            ]);

            return $invoice;
        });

        if (\Illuminate\Support\Facades\Route::has('accounting.purchase-invoices.show')) {
            return redirect()->route('accounting.purchase-invoices.show', $invoice)
                ->with('success', 'تم تحويل أمر الشراء إلى فاتورة شراء بنجاح.');
        }

        return back()->with('success', 'تم تحويل أمر الشراء إلى فاتورة شراء بنجاح. رقم الفاتورة: ' . $invoice->id);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Abort with 403 if the PO does not belong to the authenticated user's company.
     */
    private function authorizeCompany(Request $request, PurchaseOrder $purchaseOrder): void
    {
        abort_unless(
            $purchaseOrder->company_id === $request->user()->company_id,
            403
        );
    }

    /**
     * Generate the next sequential PO number for the company.
     * Format: PO-{YEAR}-{SEQUENCE padded to 4 digits}
     * e.g. PO-2026-0001
     */
    private function generatePoNumber(int $companyId): string
    {
        $year = now()->year;

        $prefix = "PO-{$year}-";

        $last = PurchaseOrder::where('company_id', $companyId)
            ->where('po_number', 'like', $prefix . '%')
            ->orderByDesc('po_number')
            ->value('po_number');

        if ($last) {
            $lastSeq = (int) substr($last, strlen($prefix));
            $seq     = $lastSeq + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}

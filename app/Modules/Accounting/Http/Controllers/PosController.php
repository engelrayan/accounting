<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Customer;
use App\Modules\Accounting\Models\Invoice;
use App\Modules\Accounting\Models\Product;
use App\Modules\Accounting\Services\ActivityLogService;
use App\Modules\Accounting\Services\CompanySettingsService;
use App\Modules\Accounting\Services\InventoryService;
use App\Modules\Accounting\Services\InvoiceService;
use App\Modules\Accounting\Services\PosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        private readonly PosService $posService,
        private readonly InvoiceService $invoiceService,
        private readonly InventoryService $inventoryService,
        private readonly CompanySettingsService $settingsService,
        private readonly ActivityLogService $log,
    ) {}

    public function create(Request $request): View
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;
        $warehouse = $this->inventoryService->getDefaultWarehouse($companyId);
        $settings  = $this->settingsService->forCompany($companyId);

        $customers = Customer::forCompany($companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $walkInCustomer = $this->posService->walkInCustomer($companyId);

        $products = Product::query()
            ->where('products.company_id', $companyId)
            ->where('products.is_active', true)
            ->leftJoin('inventory_items as inventory', function ($join) use ($warehouse) {
                $join->on('inventory.product_id', '=', 'products.id')
                    ->where('inventory.warehouse_id', '=', $warehouse->id);
            })
            ->orderBy('products.type')
            ->orderBy('products.name')
            ->get([
                'products.id',
                'products.code',
                'products.barcode',
                'products.name',
                'products.type',
                'products.unit',
                'products.sale_price',
                'products.tax_rate',
                'products.description',
                DB::raw('COALESCE(inventory.quantity_on_hand, 0) as quantity_on_hand'),
            ]);

        $stats = [
            'products' => $products->where('type', 'product')->count(),
            'services' => $products->where('type', 'service')->count(),
            'lowStock' => $products->where('type', 'product')->where('quantity_on_hand', '<=', 5)->count(),
        ];

        $nextNumber = $this->invoiceService->nextNumber($companyId);

        return view('accounting.pos.create', compact(
            'customers',
            'walkInCustomer',
            'products',
            'stats',
            'nextNumber',
            'settings',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'customer_id'        => ['nullable', 'integer', 'exists:customers,id'],
            'sale_mode'          => ['required', 'in:paid,pending'],
            'payment_method'     => ['nullable', 'string', 'in:cash,bank,bank_transfer,wallet,instapay,cheque,card,other'],
            'issue_date'         => ['required', 'date'],
            'due_date'           => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'discount_amount'    => ['nullable', 'numeric', 'min:0'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ], [
            'items.required' => 'أضف عنصرًا واحدًا على الأقل قبل إتمام البيع.',
            'items.min'      => 'أضف عنصرًا واحدًا على الأقل قبل إتمام البيع.',
        ]);

        if ($validated['sale_mode'] === 'paid' && empty($validated['payment_method'])) {
            return back()->withInput()->withErrors([
                'payment_method' => 'اختر طريقة الدفع لإتمام البيع الفوري.',
            ]);
        }

        try {
            $invoice = $this->posService->checkout($validated, $companyId);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['pos' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['pos' => 'حدث خطأ أثناء تنفيذ عملية البيع. حاول مرة أخرى.']);
        }

        $this->log->log(
            $companyId,
            'created',
            'pos_sale',
            $invoice->id,
            $invoice->invoice_number,
            "أنشأ عملية بيع من نقطة البيع برقم [{$invoice->invoice_number}] بمبلغ {$invoice->amount}."
        );

        return redirect()
            ->route('accounting.pos.receipt', $invoice)
            ->with('success', "تم تسجيل عملية البيع بنجاح تحت الفاتورة [{$invoice->invoice_number}].");
    }

    public function receipt(Request $request, Invoice $invoice): View
    {
        abort_if($invoice->company_id !== $request->user()->company_id, 403);
        abort_if(! $invoice->isPosSale(), 404);

        $invoice->load(['customer', 'items', 'creator']);

        return view('accounting.pos.receipt', compact('invoice'));
    }

    public function drawer(): View
    {
        Gate::authorize('can-write');

        return view('accounting.pos.drawer');
    }
}

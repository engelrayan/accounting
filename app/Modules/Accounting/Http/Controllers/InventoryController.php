<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\InventoryItem;
use App\Modules\Accounting\Models\InventoryMovement;
use App\Modules\Accounting\Models\Product;
use App\Modules\Accounting\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $service,
    ) {}

    // =========================================================================
    // index — قائمة جميع الأصناف مع الكميات والقيم
    // =========================================================================

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $warehouse = $this->service->getDefaultWarehouse($companyId);

        $query = InventoryItem::forCompany($companyId)
            ->with('product:id,code,name,type,unit,is_active', 'warehouse:id,name')
            ->where('warehouse_id', $warehouse->id);

        // فلتر
        if ($request->filled('q')) {
            $search = $request->q;
            $query->whereHas('product', fn ($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
            );
        }

        if ($request->filled('status')) {
            if ($request->status === 'low') {
                $query->whereRaw('quantity_on_hand <= reorder_level AND reorder_level IS NOT NULL');
            } elseif ($request->status === 'zero') {
                $query->where('quantity_on_hand', '<=', 0);
            }
        }

        $items = $query->orderByDesc('quantity_on_hand')->paginate(25)->withQueryString();

        // Summary
        $totalItems   = InventoryItem::forCompany($companyId)->where('warehouse_id', $warehouse->id)->count();
        $totalValue   = InventoryItem::forCompany($companyId)->where('warehouse_id', $warehouse->id)
            ->selectRaw('SUM(quantity_on_hand * average_cost) as total')
            ->value('total') ?? 0;
        $lowStockCount = InventoryItem::forCompany($companyId)->where('warehouse_id', $warehouse->id)
            ->whereRaw('quantity_on_hand <= reorder_level AND reorder_level IS NOT NULL')
            ->count();
        $zeroStockCount = InventoryItem::forCompany($companyId)->where('warehouse_id', $warehouse->id)
            ->where('quantity_on_hand', '<=', 0)
            ->count();

        return view('accounting.inventory.index', compact(
            'items', 'warehouse', 'totalItems', 'totalValue', 'lowStockCount', 'zeroStockCount'
        ));
    }

    // =========================================================================
    // show — تفاصيل صنف + حركاته
    // =========================================================================

    public function show(Request $request, Product $product): View
    {
        $companyId = $request->user()->company_id;

        if ($product->company_id !== $companyId) {
            abort(403);
        }

        $warehouse = $this->service->getDefaultWarehouse($companyId);

        $inventoryItem = InventoryItem::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $movements = InventoryMovement::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('accounting.inventory.show', compact('product', 'warehouse', 'inventoryItem', 'movements'));
    }

    // =========================================================================
    // adjust — تعديل يدوي للكمية
    // =========================================================================

    public function adjust(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        if ($product->company_id !== $companyId) {
            abort(403);
        }

        $validated = $request->validate([
            'quantity'  => ['required', 'numeric', 'not_in:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes'     => ['nullable', 'string', 'max:500'],
        ], [
            'quantity.required' => 'الكمية مطلوبة.',
            'quantity.not_in'   => 'الكمية يجب أن تكون موجبة أو سالبة (ليست صفراً).',
        ]);

        $warehouse = $this->service->getDefaultWarehouse($companyId);

        try {
            $this->service->recordAdjustment(
                $product,
                $warehouse,
                (float) $validated['quantity'],
                (float) ($validated['unit_cost'] ?? 0),
                $validated['notes'] ?? '',
                $request->user()->id,
                $companyId,
            );
        } catch (\Exception $e) {
            return back()->withErrors(['adjust' => $e->getMessage()]);
        }

        return redirect()
            ->route('accounting.inventory.show', $product)
            ->with('success', 'تم تسجيل التسوية اليدوية بنجاح.');
    }

    // =========================================================================
    // movements — سجل حركات المخزون الكاملة
    // =========================================================================

    public function movements(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $query = InventoryMovement::forCompany($companyId)
            ->with('product:id,code,name', 'warehouse:id,name');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('type')) {
            $query->where('movement_type', $request->type);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $movements = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(30)->withQueryString();

        $products = Product::forCompany($companyId)->active()->orderBy('name')->get(['id', 'code', 'name']);

        return view('accounting.inventory.movements', compact('movements', 'products'));
    }
}

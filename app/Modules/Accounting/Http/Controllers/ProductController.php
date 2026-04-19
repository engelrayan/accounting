<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $query = Product::forCompany($companyId)->orderBy('name');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        if ($request->filled('q')) {
            $query->search($request->input('q'));
        }

        $products     = $query->with('account')->get();
        $totalCount   = Product::forCompany($companyId)->count();
        $activeCount  = Product::forCompany($companyId)->active()->count();
        $productCount = Product::forCompany($companyId)->where('type', 'product')->count();
        $serviceCount = Product::forCompany($companyId)->where('type', 'service')->count();

        return view('accounting.products.index', compact(
            'products',
            'totalCount',
            'activeCount',
            'productCount',
            'serviceCount',
        ));
    }

    // -------------------------------------------------------------------------
    // Create / Store
    // -------------------------------------------------------------------------

    public function create(Request $request): View
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;
        $accounts  = Account::forTenant($companyId)
            ->where('is_active', true)
            ->whereIn('type', ['revenue', 'expense'])
            ->orderBy('code')
            ->get();

        return view('accounting.products.create', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'code'           => [
                'nullable', 'string', 'max:100',
                Rule::unique('products')->where('company_id', $companyId)->whereNotNull('code'),
            ],
            'barcode'        => [
                'nullable', 'string', 'max:120',
                Rule::unique('products')->where('company_id', $companyId)->whereNotNull('barcode'),
            ],
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:2000',
            'type'           => 'required|in:product,service',
            'unit'           => 'nullable|string|max:50',
            'sale_price'     => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'tax_rate'       => 'nullable|numeric|min:0|max:100',
            'account_id'     => 'nullable|exists:accounts,id',
            'is_active'      => 'boolean',
        ], [
            'name.required'       => 'اسم المنتج/الخدمة مطلوب.',
            'type.required'       => 'نوع العنصر مطلوب.',
            'sale_price.required' => 'سعر البيع مطلوب.',
            'code.unique'         => 'الرمز مستخدم بالفعل في شركتك.',
            'barcode.unique'      => 'الباركود مستخدم بالفعل في شركتك.',
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_active']  = $request->boolean('is_active', true);
        $validated['tax_rate']   = $validated['tax_rate'] ?? 0;

        // Validate account belongs to company
        if (!empty($validated['account_id'])) {
            $account = Account::forTenant($companyId)->find($validated['account_id']);
            if (!$account) {
                return back()->withErrors(['account_id' => 'الحساب غير موجود.'])->withInput();
            }
        }

        $product = Product::create($validated);

        return redirect()
            ->route('accounting.products.index')
            ->with('success', "تم إضافة [{$product->name}] إلى الكتالوج.");
    }

    // -------------------------------------------------------------------------
    // Edit / Update
    // -------------------------------------------------------------------------

    public function edit(Request $request, Product $product): View
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $product);

        $companyId = $request->user()->company_id;
        $accounts  = Account::forTenant($companyId)
            ->where('is_active', true)
            ->whereIn('type', ['revenue', 'expense'])
            ->orderBy('code')
            ->get();

        return view('accounting.products.edit', compact('product', 'accounts'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $product);

        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'code'           => [
                'nullable', 'string', 'max:100',
                Rule::unique('products')->where('company_id', $companyId)
                    ->whereNotNull('code')->ignore($product->id),
            ],
            'barcode'        => [
                'nullable', 'string', 'max:120',
                Rule::unique('products')->where('company_id', $companyId)
                    ->whereNotNull('barcode')->ignore($product->id),
            ],
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:2000',
            'type'           => 'required|in:product,service',
            'unit'           => 'nullable|string|max:50',
            'sale_price'     => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'tax_rate'       => 'nullable|numeric|min:0|max:100',
            'account_id'     => 'nullable|exists:accounts,id',
            'is_active'      => 'boolean',
        ], [
            'name.required'       => 'اسم المنتج/الخدمة مطلوب.',
            'sale_price.required' => 'سعر البيع مطلوب.',
            'code.unique'         => 'الرمز مستخدم بالفعل في شركتك.',
            'barcode.unique'      => 'الباركود مستخدم بالفعل في شركتك.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['tax_rate']  = $validated['tax_rate'] ?? 0;

        if (!empty($validated['account_id'])) {
            $account = Account::forTenant($companyId)->find($validated['account_id']);
            if (!$account) {
                return back()->withErrors(['account_id' => 'الحساب غير موجود.'])->withInput();
            }
        }

        $product->update($validated);

        return redirect()
            ->route('accounting.products.index')
            ->with('success', "تم تحديث [{$product->name}] بنجاح.");
    }

    // -------------------------------------------------------------------------
    // Toggle active
    // -------------------------------------------------------------------------

    public function toggle(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $product);

        $product->update(['is_active' => !$product->is_active]);

        $state = $product->is_active ? 'تفعيل' : 'تعطيل';

        return back()->with('success', "تم {$state} [{$product->name}].");
    }

    // -------------------------------------------------------------------------
    // Search (JSON — for catalog modal)
    // -------------------------------------------------------------------------

    public function search(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $query = Product::forCompany($companyId)->active();

        if ($request->filled('q')) {
            $query->search($request->input('q'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $products = $query->orderBy('name')->limit(50)->get([
            'id', 'code', 'barcode', 'name', 'type', 'unit',
            'sale_price', 'purchase_price', 'tax_rate', 'description',
        ]);

        return response()->json($products);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function authorizeCompany(Request $request, Product $product): void
    {
        if ($product->company_id !== $request->user()->company_id) {
            abort(403);
        }
    }
}

<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Customer;
use App\Modules\Accounting\Models\Governorate;
use App\Modules\Accounting\Models\PriceList;
use App\Modules\Accounting\Models\PriceListItem;
use App\Modules\Accounting\Services\PriceResolverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PriceListController extends Controller
{
    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $query = PriceList::forCompany($companyId)
            ->withCount(['items', 'customers'])
            ->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->input('q') . '%');
        }

        $priceLists  = $query->get();
        $totalCount  = PriceList::forCompany($companyId)->count();
        $activeCount = PriceList::forCompany($companyId)->active()->count();

        return view('accounting.price-lists.index', compact(
            'priceLists',
            'totalCount',
            'activeCount',
        ));
    }

    // -------------------------------------------------------------------------
    // Create / Store
    // -------------------------------------------------------------------------

    public function create(Request $request): View
    {
        Gate::authorize('can-write');

        $companyId    = $request->user()->company_id;
        $governorates = Governorate::active()->ordered()->get();
        $customers    = Customer::forCompany($companyId)->orderBy('name')->get(['id', 'name']);

        return view('accounting.price-lists.create', compact('governorates', 'customers'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        $request->validate([
            'name'                    => 'required|string|max:255',
            'description'             => 'nullable|string|max:2000',
            'govs'                    => 'nullable|array',
            'govs.*.price'            => 'nullable|numeric|min:0',
            'govs.*.return_price'     => 'nullable|numeric|min:0',
            'customer_ids'            => 'nullable|array',
            'customer_ids.*'          => 'integer|exists:customers,id',
        ], [
            'name.required' => 'اسم قائمة الأسعار مطلوب.',
        ]);

        $govs         = $request->input('govs', []);
        $customerIds  = array_filter(array_map('intval', $request->input('customer_ids', [])));
        $validGovIds  = Governorate::active()->pluck('id')->all();

        $priceList = DB::transaction(function () use ($request, $govs, $customerIds, $validGovIds, $companyId) {
            $pl = PriceList::create([
                'company_id'  => $companyId,
                'name'        => $request->input('name'),
                'description' => $request->input('description'),
                'is_active'   => $request->boolean('is_active', true),
            ]);

            // ── Price List Items ──
            $items = [];
            $now   = now();
            foreach ($govs as $govId => $govData) {
                if (!in_array((int) $govId, $validGovIds))       continue;
                if (empty($govData['enabled']))                   continue;
                if (!isset($govData['price']) || trim((string) $govData['price']) === '') continue;

                $returnPrice = (isset($govData['return_price']) && trim((string) $govData['return_price']) !== '')
                    ? (float) $govData['return_price']
                    : null;

                $items[] = [
                    'price_list_id'  => $pl->id,
                    'governorate_id' => (int) $govId,
                    'price'          => (float) $govData['price'],
                    'return_price'   => $returnPrice,
                    'notes'          => $govData['notes'] ?? null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            if ($items) {
                PriceListItem::insert($items);
            }

            // ── Customer Assignment ──
            if ($customerIds) {
                $pl->customers()->sync($customerIds);
            }

            return $pl;
        });

        return redirect()
            ->route('accounting.price-lists.show', $priceList)
            ->with('success', "تم إنشاء قائمة الأسعار [{$priceList->name}] بنجاح.");
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(Request $request, PriceList $priceList): View
    {
        $this->authorizeCompany($request, $priceList);

        $priceList->load(['items.governorate', 'customers']);
        $governorates    = Governorate::active()->ordered()->get();
        $itemsMap        = $priceList->itemsMap();
        $returnPricesMap = $priceList->returnPricesMap();

        return view('accounting.price-lists.show', compact(
            'priceList',
            'governorates',
            'itemsMap',
            'returnPricesMap',
        ));
    }

    // -------------------------------------------------------------------------
    // Edit / Update
    // -------------------------------------------------------------------------

    public function edit(Request $request, PriceList $priceList): View
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $priceList);

        $companyId    = $request->user()->company_id;
        $priceList->load(['items.governorate', 'customers']);
        $governorates    = Governorate::active()->ordered()->get();
        $customers       = Customer::forCompany($companyId)->orderBy('name')->get(['id', 'name']);
        $itemsMap        = $priceList->itemsMap();
        $returnPricesMap = $priceList->returnPricesMap();
        $notesMap        = $priceList->notesMap();
        $linkedCustomers = $priceList->customers->pluck('id')->all();

        return view('accounting.price-lists.edit', compact(
            'priceList',
            'governorates',
            'customers',
            'itemsMap',
            'returnPricesMap',
            'notesMap',
            'linkedCustomers',
        ));
    }

    public function update(Request $request, PriceList $priceList): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $priceList);

        $request->validate([
            'name'                    => 'required|string|max:255',
            'description'             => 'nullable|string|max:2000',
            'govs'                    => 'nullable|array',
            'govs.*.price'            => 'nullable|numeric|min:0',
            'govs.*.return_price'     => 'nullable|numeric|min:0',
            'customer_ids'            => 'nullable|array',
            'customer_ids.*'          => 'integer|exists:customers,id',
        ], [
            'name.required' => 'اسم قائمة الأسعار مطلوب.',
        ]);

        $govs        = $request->input('govs', []);
        $customerIds = array_filter(array_map('intval', $request->input('customer_ids', [])));
        $validGovIds = Governorate::active()->pluck('id')->all();

        DB::transaction(function () use ($request, $govs, $customerIds, $validGovIds, $priceList) {
            $priceList->update([
                'name'        => $request->input('name'),
                'description' => $request->input('description'),
                'is_active'   => $request->boolean('is_active', true),
            ]);

            // ── Price List Items (delete + re-insert) ──
            $priceList->items()->delete();

            $items = [];
            $now   = now();
            foreach ($govs as $govId => $govData) {
                if (!in_array((int) $govId, $validGovIds))       continue;
                if (empty($govData['enabled']))                   continue;
                if (!isset($govData['price']) || trim((string) $govData['price']) === '') continue;

                $returnPrice = (isset($govData['return_price']) && trim((string) $govData['return_price']) !== '')
                    ? (float) $govData['return_price']
                    : null;

                $items[] = [
                    'price_list_id'  => $priceList->id,
                    'governorate_id' => (int) $govId,
                    'price'          => (float) $govData['price'],
                    'return_price'   => $returnPrice,
                    'notes'          => $govData['notes'] ?? null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            if ($items) {
                PriceListItem::insert($items);
            }

            // ── Customer Assignment ──
            $priceList->customers()->sync($customerIds ?: []);
        });

        return redirect()
            ->route('accounting.price-lists.show', $priceList)
            ->with('success', "تم تحديث قائمة الأسعار [{$priceList->name}] بنجاح.");
    }

    // -------------------------------------------------------------------------
    // Toggle active
    // -------------------------------------------------------------------------

    public function toggle(Request $request, PriceList $priceList): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $priceList);

        // لا يمكن تعطيل القائمة الافتراضية
        if ($priceList->is_default && $priceList->is_active) {
            return back()->withErrors(['toggle' => 'لا يمكن تعطيل القائمة الافتراضية. قم بتعيين قائمة أخرى افتراضية أولاً.']);
        }

        $priceList->update(['is_active' => !$priceList->is_active]);
        $state = $priceList->is_active ? 'تفعيل' : 'تعطيل';

        return back()->with('success', "تم {$state} قائمة الأسعار [{$priceList->name}].");
    }

    // -------------------------------------------------------------------------
    // Set / Unset Default
    // -------------------------------------------------------------------------

    public function setDefault(Request $request, PriceList $priceList): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $priceList);

        $resolver = app(PriceResolverService::class);

        if ($priceList->is_default) {
            // إلغاء الافتراضية
            $resolver->unsetDefault($request->user()->company_id, $priceList);
            return back()->with('success', "تم إلغاء تعيين [{$priceList->name}] كقائمة افتراضية.");
        }

        $resolver->setDefault($request->user()->company_id, $priceList);
        return back()->with('success', "تم تعيين [{$priceList->name}] كقائمة الأسعار الافتراضية.");
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function authorizeCompany(Request $request, PriceList $priceList): void
    {
        if ($priceList->company_id !== $request->user()->company_id) {
            abort(403);
        }
    }
}

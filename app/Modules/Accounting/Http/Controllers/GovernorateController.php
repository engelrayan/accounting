<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\Governorate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GovernorateController extends Controller
{
    // -------------------------------------------------------------------------
    // Index (+ inline create form)
    // -------------------------------------------------------------------------

    public function index(): View
    {
        $governorates = Governorate::ordered()->get();
        $totalCount   = $governorates->count();
        $activeCount  = $governorates->where('is_active', true)->count();
        $customCount  = $governorates->where('is_system', false)->count();

        return view('accounting.governorates.index', compact(
            'governorates',
            'totalCount',
            'activeCount',
            'customCount',
        ));
    }

    // -------------------------------------------------------------------------
    // Store (إضافة محافظة جديدة)
    // -------------------------------------------------------------------------

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $validated = $request->validate([
            'name_ar' => 'required|string|max:100|unique:governorates,name_ar',
        ], [
            'name_ar.required' => 'اسم المحافظة مطلوب.',
            'name_ar.unique'   => 'هذه المحافظة موجودة بالفعل.',
        ]);

        $maxOrder = Governorate::max('sort_order') ?? 0;

        Governorate::create([
            'name_ar'    => trim($validated['name_ar']),
            'is_system'  => false,
            'is_active'  => true,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('accounting.governorates.index')
            ->with('success', "تم إضافة [{$validated['name_ar']}] إلى قائمة المحافظات.");
    }

    // -------------------------------------------------------------------------
    // Toggle active
    // -------------------------------------------------------------------------

    public function toggle(Request $request, Governorate $governorate): RedirectResponse
    {
        Gate::authorize('can-write');

        $governorate->update(['is_active' => !$governorate->is_active]);
        $state = $governorate->is_active ? 'تفعيل' : 'تعطيل';

        return back()->with('success', "تم {$state} [{$governorate->name_ar}].");
    }

    // -------------------------------------------------------------------------
    // Destroy (مخصصة فقط)
    // -------------------------------------------------------------------------

    public function destroy(Request $request, Governorate $governorate): RedirectResponse
    {
        Gate::authorize('can-write');

        if ($governorate->is_system) {
            return back()->withErrors(['delete' => 'لا يمكن حذف المحافظات الافتراضية.']);
        }

        // منع الحذف إذا كانت مستخدمة في قوائم أسعار
        if ($governorate->priceListItems()->exists()) {
            return back()->withErrors([
                'delete' => "لا يمكن حذف [{$governorate->name_ar}] لأنها مستخدمة في قوائم أسعار.",
            ]);
        }

        $name = $governorate->name_ar;
        $governorate->delete();

        return redirect()
            ->route('accounting.governorates.index')
            ->with('success', "تم حذف [{$name}].");
    }
}

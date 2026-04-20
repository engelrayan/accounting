<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Services\CompanyModuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModuleSettingsController extends Controller
{
    public function __construct(
        private readonly CompanyModuleService $modules,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('admin-only');

        $modules = $this->modules->allForSettings((int) $request->user()->company_id);

        return view('accounting.settings.modules', compact('modules'));
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('admin-only');

        $keys = $this->modules->keys();

        $validated = $request->validate([
            'modules' => ['required', 'array'],
            'modules.*.key' => ['required', 'string', Rule::in($keys)],
            'modules.*.label' => ['nullable', 'string', 'max:80'],
            'modules.*.is_enabled' => ['nullable', 'boolean'],
        ]);

        $normalized = [];

        foreach ($validated['modules'] as $module) {
            $normalized[$module['key']] = [
                'label' => $module['label'] ?? null,
                'is_enabled' => (bool) ($module['is_enabled'] ?? false),
            ];
        }

        $this->modules->updateForCompany((int) $request->user()->company_id, $normalized);

        return back()->with('success', 'تم حفظ تخصيص القائمة بنجاح.');
    }

    public function reset(Request $request): RedirectResponse
    {
        Gate::authorize('admin-only');

        $this->modules->resetForCompany((int) $request->user()->company_id);

        return back()->with('success', 'تمت إعادة القائمة للوضع الافتراضي.');
    }
}

<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Services\CompanySettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function __construct(
        private readonly CompanySettingsService $service,
    ) {}

    public function index(Request $request): View
    {
        $settings = $this->service->forCompany($request->user()->company_id);

        return view('accounting.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $request->validate([
            'tax_enabled'         => ['nullable', 'boolean'],
            'tax_rate'            => ['required_if:tax_enabled,1', 'nullable', 'numeric', 'min:0', 'max:100'],
            'tax_name'            => ['nullable', 'string', 'max:100'],
            'tax_account_code'    => ['nullable', 'string', 'max:20'],
            'tax_number'          => ['nullable', 'string', 'max:50'],
            'currency'            => ['nullable', 'string', 'max:10'],
            'fiscal_year_start'   => ['nullable', 'string', 'regex:/^\d{2}-\d{2}$/'],
            'invoice_footer_note' => ['nullable', 'string', 'max:500'],
            'company_name_ar'     => ['nullable', 'string', 'max:200'],
            'company_address'     => ['nullable', 'string', 'max:500'],
        ], [
            'tax_rate.required_if' => 'نسبة الضريبة مطلوبة عند تفعيل الضريبة.',
            'tax_rate.max'         => 'نسبة الضريبة لا تتجاوز 100%.',
        ]);

        // Normalize: checkbox not sent when unchecked
        $input = $request->except('_token', '_method');
        $input['tax_enabled'] = $request->boolean('tax_enabled');

        $this->service->update($request->user()->company_id, $input);

        return back()->with('success', 'تم حفظ الإعدادات بنجاح.');
    }
}

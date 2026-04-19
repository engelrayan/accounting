@extends('accounting._layout')

@section('title', 'إعدادات الشركة')

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">إعدادات الشركة</h1>
</div>

@if(session('success'))
    <div class="ac-alert ac-alert--success" data-dismiss="alert">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('accounting.settings.update') }}">
    @csrf
    @method('PATCH')

    <div class="ac-settings-grid">

        {{-- ── الضرائب ────────────────────────────────────────────────────────── --}}
        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14,2 14,8 20,8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    الضريبة
                </p>

                {{-- تفعيل الضريبة --}}
                <div class="ac-settings-toggle-row">
                    <div>
                        <div class="ac-settings-toggle-label">تفعيل ضريبة القيمة المضافة</div>
                        <div class="ac-settings-toggle-hint">
                            عند التفعيل ستُضاف الضريبة تلقائياً لكل فاتورة جديدة ويمكن تعديلها لكل فاتورة على حدة.
                        </div>
                    </div>
                    <label class="ac-toggle">
                        <input type="checkbox" name="tax_enabled" value="1"
                               id="tax_enabled"
                               {{ $settings->taxEnabled() ? 'checked' : '' }}>
                        <span class="ac-toggle__track"></span>
                    </label>
                </div>

                {{-- حقول الضريبة — تظهر/تختفي بـ JS --}}
                <div id="tax-fields" class="{{ $settings->taxEnabled() ? '' : 'ac-hidden' }}">

                    <div class="ac-form-row ac-mt-3">
                        <div class="ac-form-group">
                            <label class="ac-label ac-label--required" for="tax_rate">نسبة الضريبة %</label>
                            <input type="number" step="0.01" min="0" max="100"
                                   id="tax_rate" name="tax_rate"
                                   class="ac-control ac-control--num {{ $errors->has('tax_rate') ? 'ac-control--error' : '' }}"
                                   value="{{ old('tax_rate', $settings->taxRate()) }}">
                            @error('tax_rate') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="ac-form-group">
                            <label class="ac-label" for="tax_name">اسم الضريبة</label>
                            <input type="text" id="tax_name" name="tax_name"
                                   class="ac-control"
                                   value="{{ old('tax_name', $settings->taxName()) }}"
                                   placeholder="ضريبة القيمة المضافة">
                        </div>

                        <div class="ac-form-group">
                            <label class="ac-label" for="tax_number">الرقم الضريبي للشركة</label>
                            <input type="text" id="tax_number" name="tax_number"
                                   class="ac-control"
                                   value="{{ old('tax_number', $settings->get('tax_number')) }}"
                                   placeholder="123456789">
                        </div>

                        <div class="ac-form-group">
                            <label class="ac-label" for="tax_account_code">كود حساب الضريبة</label>
                            <input type="text" id="tax_account_code" name="tax_account_code"
                                   class="ac-control"
                                   value="{{ old('tax_account_code', $settings->get('tax_account_code')) }}"
                                   placeholder="2300">
                            <span class="ac-field-hint">الحساب الذي يُرحَّل إليه الضريبة في دليل الحسابات</span>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        {{-- ── الشركة ──────────────────────────────────────────────────────────── --}}
        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                    بيانات الشركة على الفاتورة
                </p>

                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label" for="company_name_ar">اسم الشركة بالعربي</label>
                        <input type="text" id="company_name_ar" name="company_name_ar"
                               class="ac-control"
                               value="{{ old('company_name_ar', $settings->get('company_name_ar')) }}"
                               placeholder="شركة سنباد للتجارة">
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label" for="currency">العملة</label>
                        <select id="currency" name="currency" class="ac-select">
                            @foreach(['EGP' => 'جنيه مصري', 'USD' => 'دولار أمريكي', 'EUR' => 'يورو', 'SAR' => 'ريال سعودي', 'AED' => 'درهم إماراتي'] as $code => $label)
                                <option value="{{ $code }}"
                                    {{ old('currency', $settings->currency()) === $code ? 'selected' : '' }}>
                                    {{ $code }} — {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label" for="fiscal_year_start">بداية السنة المالية</label>
                        <select id="fiscal_year_start" name="fiscal_year_start" class="ac-select">
                            @foreach([
                                '01-01' => 'يناير (01)',
                                '04-01' => 'أبريل (04)',
                                '07-01' => 'يوليو (07)',
                                '10-01' => 'أكتوبر (10)',
                            ] as $val => $lbl)
                                <option value="{{ $val }}"
                                    {{ old('fiscal_year_start', $settings->get('fiscal_year_start')) === $val ? 'selected' : '' }}>
                                    {{ $lbl }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="company_address">عنوان الشركة</label>
                    <input type="text" id="company_address" name="company_address"
                           class="ac-control"
                           value="{{ old('company_address', $settings->get('company_address')) }}"
                           placeholder="القاهرة — مصر">
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="invoice_footer_note">ملاحظة أسفل الفاتورة</label>
                    <textarea id="invoice_footer_note" name="invoice_footer_note"
                              rows="2" class="ac-control"
                              placeholder="شكراً لتعاملكم معنا. شروط الدفع: 30 يوم.">{{ old('invoice_footer_note', $settings->get('invoice_footer_note')) }}</textarea>
                </div>

            </div>
        </div>

    </div>

    <div class="ac-settings-actions">
        <button type="submit" class="ac-btn ac-btn--primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            حفظ الإعدادات
        </button>
    </div>

</form>

@endsection

@push('scripts')
<script>
(function () {
    const toggle = document.getElementById('tax_enabled');
    const fields = document.getElementById('tax-fields');
    const rateInput = document.getElementById('tax_rate');

    toggle.addEventListener('change', function () {
        fields.classList.toggle('ac-hidden', !this.checked);
        rateInput.required = this.checked;
    });

    // Set initial required state
    rateInput.required = toggle.checked;
})();
</script>
@endpush

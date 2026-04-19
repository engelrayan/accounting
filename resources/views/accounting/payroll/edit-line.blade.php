@extends('accounting._layout')

@section('title', 'تعديل بيانات: ' . $payrollLine->employee->name)

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">
        تعديل بيانات الموظف — {{ $payrollLine->employee->name }}
        <span style="font-weight:400;color:var(--ac-text-muted);font-size:.85rem">
            {{ $payrollRun->periodLabel() }}
        </span>
    </h1>
    <a href="{{ route('accounting.payroll.show', $payrollRun) }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>

@if($errors->has('line'))
    <div class="ac-alert ac-alert--error">{{ $errors->first('line') }}</div>
@endif

<form method="POST" action="{{ route('accounting.payroll.update-line', [$payrollRun, $payrollLine]) }}" id="line-form">
    @csrf

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

        {{-- ── معلومات الموظف (قراءة فقط) ─────────────────────────────── --}}
        <div class="ac-card">
            <div class="ac-card__header"><span class="ac-card__title">بيانات الموظف</span></div>
            <div class="ac-card__body" style="font-size:.88rem">
                <div style="display:flex;flex-direction:column;gap:10px">
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--ac-text-muted)">رقم الموظف</span>
                        <span style="font-weight:600">{{ $payrollLine->employee->employee_number }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--ac-text-muted)">القسم</span>
                        <span>{{ $payrollLine->employee->department ?? '—' }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--ac-text-muted)">المسمى الوظيفي</span>
                        <span>{{ $payrollLine->employee->position ?? '—' }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:var(--ac-text-muted)">تاريخ التعيين</span>
                        <span>{{ $payrollLine->employee->hire_date->format('Y-m-d') }}</span>
                    </div>
                </div>

                <hr style="margin:16px 0;border:none;border-top:1px solid var(--ac-border)">

                <div class="ac-form-group">
                    <label class="ac-label ac-label--required" for="basic_salary">الراتب الأساسي</label>
                    <input id="basic_salary" name="basic_salary" type="number" step="0.01" min="0"
                           class="ac-control {{ $errors->has('basic_salary') ? 'ac-control--error' : '' }}"
                           value="{{ old('basic_salary', $payrollLine->basic_salary) }}"
                           oninput="recalc()" required>
                    @error('basic_salary') <span class="ac-field-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="payment_method">طريقة الصرف</label>
                    <select id="payment_method" name="payment_method" class="ac-select">
                        <option value="bank"  {{ old('payment_method', $payrollLine->payment_method) === 'bank'  ? 'selected' : '' }}>بنكي</option>
                        <option value="cash"  {{ old('payment_method', $payrollLine->payment_method) === 'cash'  ? 'selected' : '' }}>نقداً</option>
                        <option value="other" {{ old('payment_method', $payrollLine->payment_method) === 'other' ? 'selected' : '' }}>أخرى</option>
                    </select>
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="notes">ملاحظات</label>
                    <textarea id="notes" name="notes" rows="2" class="ac-control"
                              placeholder="أي ملاحظات خاصة...">{{ old('notes', $payrollLine->notes) }}</textarea>
                </div>

            </div>
        </div>

        {{-- ── البدلات والخصومات ──────────────────────────────────────── --}}
        <div style="display:flex;flex-direction:column;gap:16px">

            {{-- البدلات --}}
            <div class="ac-card">
                <div class="ac-card__header" style="display:flex;align-items:center;justify-content:space-between">
                    <span class="ac-card__title">البدلات</span>
                    <button type="button" onclick="addRow('allowances')"
                            class="ac-btn ac-btn--ghost ac-btn--sm">
                        + إضافة بدل
                    </button>
                </div>
                <div class="ac-card__body">
                    <div id="allowances-rows">
                        @php $allowances = old('allowances', $payrollLine->allowances ?? []); @endphp
                        @forelse($allowances as $i => $allow)
                        <div class="pay-item-row" style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
                            <input type="text" name="allowances[{{ $i }}][name]"
                                   class="ac-control ac-control--sm" style="flex:1"
                                   placeholder="اسم البدل" value="{{ $allow['name'] ?? '' }}">
                            <input type="number" name="allowances[{{ $i }}][amount]"
                                   class="ac-control ac-control--sm ac-control--num pay-amount" style="width:110px"
                                   placeholder="0.00" step="0.01" min="0"
                                   value="{{ $allow['amount'] ?? '' }}"
                                   oninput="recalc()">
                            <button type="button" onclick="removeRow(this)"
                                    class="ac-btn ac-btn--ghost ac-btn--sm" style="color:var(--ac-danger)">✕</button>
                        </div>
                        @empty
                        <div id="allowances-empty" style="color:var(--ac-text-muted);font-size:.83rem;padding:4px 0">
                            لا توجد بدلات. انقر "إضافة بدل" لإضافة بدل.
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- الخصومات --}}
            <div class="ac-card">
                <div class="ac-card__header" style="display:flex;align-items:center;justify-content:space-between">
                    <span class="ac-card__title">الخصومات</span>
                    <button type="button" onclick="addRow('deductions')"
                            class="ac-btn ac-btn--ghost ac-btn--sm">
                        + إضافة خصم
                    </button>
                </div>
                <div class="ac-card__body">
                    <div id="deductions-rows">
                        @php $deductions = old('deductions', $payrollLine->deductions ?? []); @endphp
                        @forelse($deductions as $i => $ded)
                        <div class="pay-item-row" style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
                            <input type="text" name="deductions[{{ $i }}][name]"
                                   class="ac-control ac-control--sm" style="flex:1"
                                   placeholder="اسم الخصم" value="{{ $ded['name'] ?? '' }}">
                            <input type="number" name="deductions[{{ $i }}][amount]"
                                   class="ac-control ac-control--sm ac-control--num pay-amount" style="width:110px"
                                   placeholder="0.00" step="0.01" min="0"
                                   value="{{ $ded['amount'] ?? '' }}"
                                   oninput="recalc()">
                            <button type="button" onclick="removeRow(this)"
                                    class="ac-btn ac-btn--ghost ac-btn--sm" style="color:var(--ac-danger)">✕</button>
                        </div>
                        @empty
                        <div id="deductions-empty" style="color:var(--ac-text-muted);font-size:.83rem;padding:4px 0">
                            لا توجد خصومات. انقر "إضافة خصم" لإضافة خصم.
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ملخص حي ──────────────────────────────────────────────────── --}}
            <div class="ac-card" style="background:var(--ac-primary-light,#f0f4ff)">
                <div class="ac-card__body" style="font-size:.9rem">
                    <div style="display:flex;justify-content:space-between;padding:5px 0">
                        <span>الراتب الأساسي</span>
                        <span id="sum-basic" style="font-variant-numeric:tabular-nums;font-weight:600">
                            {{ number_format($payrollLine->basic_salary, 2) }}
                        </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:5px 0;color:var(--ac-success)">
                        <span>+ البدلات</span>
                        <span id="sum-allow" style="font-variant-numeric:tabular-nums">
                            {{ number_format($payrollLine->totalAllowances(), 2) }}
                        </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--ac-border)">
                        <span>= الإجمالي</span>
                        <span id="sum-gross" style="font-variant-numeric:tabular-nums;font-weight:600">
                            {{ number_format($payrollLine->gross_salary, 2) }}
                        </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:5px 0;color:var(--ac-danger)">
                        <span>- الخصومات</span>
                        <span id="sum-deduct" style="font-variant-numeric:tabular-nums">
                            {{ number_format($payrollLine->totalDeductions(), 2) }}
                        </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 0 0;font-size:1.1rem;font-weight:800">
                        <span>صافي الراتب</span>
                        <span id="sum-net" style="font-variant-numeric:tabular-nums">
                            {{ number_format($payrollLine->net_salary, 2) }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div style="margin-top:16px;display:flex;gap:10px;justify-content:flex-end">
        <a href="{{ route('accounting.payroll.show', $payrollRun) }}" class="ac-btn ac-btn--secondary">إلغاء</a>
        <button type="submit" class="ac-btn ac-btn--primary">حفظ البيانات</button>
    </div>
</form>

@push('scripts')
<script>
(function () {
    let allowIdx = {{ count($allowances) }};
    let deductIdx = {{ count($deductions) }};

    window.addRow = function(section) {
        const container = document.getElementById(section + '-rows');
        const empty = document.getElementById(section + '-empty');
        if (empty) empty.remove();

        const idx = section === 'allowances' ? allowIdx++ : deductIdx++;
        const placeholder = section === 'allowances' ? 'اسم البدل' : 'اسم الخصم';

        const div = document.createElement('div');
        div.className = 'pay-item-row';
        div.style.cssText = 'display:flex;gap:8px;margin-bottom:8px;align-items:center';
        div.innerHTML = `
            <input type="text" name="${section}[${idx}][name]"
                   class="ac-control ac-control--sm" style="flex:1" placeholder="${placeholder}">
            <input type="number" name="${section}[${idx}][amount]"
                   class="ac-control ac-control--sm ac-control--num pay-amount" style="width:110px"
                   placeholder="0.00" step="0.01" min="0" oninput="recalc()">
            <button type="button" onclick="removeRow(this)"
                    class="ac-btn ac-btn--ghost ac-btn--sm" style="color:var(--ac-danger)">✕</button>`;
        container.appendChild(div);
        div.querySelector('input[type=text]').focus();
    };

    window.removeRow = function(btn) {
        btn.closest('.pay-item-row').remove();
        recalc();
    };

    window.recalc = function() {
        const basic = parseFloat(document.getElementById('basic_salary').value) || 0;

        let totalAllow = 0;
        document.querySelectorAll('#allowances-rows .pay-amount').forEach(inp => {
            totalAllow += parseFloat(inp.value) || 0;
        });

        let totalDeduct = 0;
        document.querySelectorAll('#deductions-rows .pay-amount').forEach(inp => {
            totalDeduct += parseFloat(inp.value) || 0;
        });

        const gross = basic + totalAllow;
        const net   = Math.max(0, gross - totalDeduct);

        const fmt = v => v.toLocaleString('ar-EG', {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('sum-basic').textContent  = fmt(basic);
        document.getElementById('sum-allow').textContent  = fmt(totalAllow);
        document.getElementById('sum-gross').textContent  = fmt(gross);
        document.getElementById('sum-deduct').textContent = fmt(totalDeduct);
        document.getElementById('sum-net').textContent    = fmt(net);
    };
})();
</script>
@endpush

@endsection

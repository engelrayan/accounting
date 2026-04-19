@extends('accounting._layout')

@section('title', 'إشعار دائن جديد')

@section('topbar-actions')
<div class="ac-topbar-actions">
    <a href="{{ route('accounting.credit-notes.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>
@endsection

@section('content')

@if($errors->has('credit_note'))
    <div class="ac-alert ac-alert--error">{{ $errors->first('credit_note') }}</div>
@endif

<div class="ac-cn-create-layout">

    {{-- ══ Form ══════════════════════════════════════════════════════════════ --}}
    <div class="ac-cn-form-col">
        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">بيانات الإشعار الدائن</p>

                <form method="POST" action="{{ route('accounting.credit-notes.store') }}"
                      id="cn-form">
                    @csrf

                    {{-- رقم الإشعار (عرض فقط) --}}
                    <div class="ac-form-group">
                        <label class="ac-label">رقم الإشعار</label>
                        <input type="text" class="ac-control ac-control--readonly"
                               value="{{ $nextNumber }}" readonly>
                    </div>

                    {{-- الفاتورة --}}
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="invoice_id">الفاتورة</label>
                        <select id="invoice_id" name="invoice_id"
                                class="ac-control {{ $errors->has('invoice_id') ? 'ac-control--error' : '' }}"
                                required onchange="onInvoiceChange(this)">
                            <option value="">— اختر الفاتورة —</option>
                            @foreach($invoices as $inv)
                            <option value="{{ $inv->id }}"
                                    data-remaining="{{ $inv->remaining_amount }}"
                                    data-tax-rate="{{ $inv->tax_rate }}"
                                    data-number="{{ $inv->invoice_number }}"
                                    {{ (old('invoice_id', $preselectedInvoice?->id) == $inv->id) ? 'selected' : '' }}>
                                {{ $inv->invoice_number }} — {{ $inv->customer?->name }}
                                (متبقي: {{ number_format($inv->remaining_amount, 2) }})
                            </option>
                            @endforeach
                        </select>
                        @error('invoice_id') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- تاريخ الإشعار --}}
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="issue_date">تاريخ الإشعار</label>
                        <input type="date" id="issue_date" name="issue_date"
                               class="ac-control {{ $errors->has('issue_date') ? 'ac-control--error' : '' }}"
                               value="{{ old('issue_date', today()->toDateString()) }}" required>
                        @error('issue_date') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- المبلغ (قبل الضريبة) --}}
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="amount">
                            المبلغ قبل الضريبة
                        </label>
                        <div class="ac-pay-amount-row">
                            <input type="number" step="0.01" min="0.01"
                                   id="amount" name="amount"
                                   class="ac-control ac-control--num {{ $errors->has('amount') ? 'ac-control--error' : '' }}"
                                   value="{{ old('amount') }}"
                                   placeholder="0.00" required
                                   oninput="recalc()">
                            <button type="button" class="ac-btn ac-btn--secondary ac-btn--sm"
                                    onclick="fillMax()">الكامل</button>
                        </div>
                        @error('amount') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- ملخص محسوب --}}
                    <div class="ac-cn-calc-box" id="cn-calc-box" style="display:none">
                        <div class="ac-cn-calc-row">
                            <span>المبلغ قبل الضريبة</span>
                            <span id="calc-amount">0.00</span>
                        </div>
                        <div class="ac-cn-calc-row" id="calc-tax-row">
                            <span>الضريبة (<span id="calc-tax-rate">0</span>%)</span>
                            <span id="calc-tax">0.00</span>
                        </div>
                        <div class="ac-cn-calc-row ac-cn-calc-row--total">
                            <span>الإجمالي</span>
                            <span id="calc-total">0.00</span>
                        </div>
                        <div class="ac-cn-calc-row ac-cn-calc-row--remaining">
                            <span>المتبقي بعد الإشعار</span>
                            <span id="calc-after">—</span>
                        </div>
                    </div>

                    {{-- السبب --}}
                    <div class="ac-form-group">
                        <label class="ac-label" for="reason">سبب الإشعار</label>
                        <textarea id="reason" name="reason" rows="3"
                                  class="ac-control"
                                  placeholder="مثال: رد بضاعة معيبة، خصم خاص، تصحيح فاتورة...">{{ old('reason') }}</textarea>
                    </div>

                    <button type="submit" class="ac-btn ac-btn--danger ac-btn--full"
                            onclick="return confirm('هل تريد إصدار هذا الإشعار الدائن؟ لا يمكن التراجع عنه.')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        إصدار الإشعار الدائن
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ══ Info panel ═════════════════════════════════════════════════════════ --}}
    <div class="ac-cn-info-col">
        <div class="ac-card" id="invoice-info-card" style="display:none">
            <div class="ac-card__body">
                <p class="ac-section-label">بيانات الفاتورة المحددة</p>
                <div class="ac-cn-inv-meta">
                    <div class="ac-cn-inv-meta__row">
                        <span class="ac-cn-inv-meta__key">رقم الفاتورة</span>
                        <span class="ac-cn-inv-meta__val" id="inv-number">—</span>
                    </div>
                    <div class="ac-cn-inv-meta__row">
                        <span class="ac-cn-inv-meta__key">المتبقي</span>
                        <span class="ac-cn-inv-meta__val ac-text-danger" id="inv-remaining">—</span>
                    </div>
                    <div class="ac-cn-inv-meta__row">
                        <span class="ac-cn-inv-meta__key">نسبة الضريبة</span>
                        <span class="ac-cn-inv-meta__val" id="inv-tax-rate">—</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="ac-card">
            <div class="ac-card__body ac-cn-help">
                <p class="ac-section-label">تنبيه</p>
                <ul class="ac-cn-help-list">
                    <li>الإشعار الدائن يُنقص المتبقي على الفاتورة الأصلية.</li>
                    <li>الضريبة تُحسب تلقائياً بنفس نسبة الفاتورة الأصلية.</li>
                    <li>بعد الإصدار تُنشأ قيود محاسبية تلقائية.</li>
                    <li>لا يمكن إلغاء الإشعار بعد الإصدار.</li>
                </ul>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
let currentRemaining = 0;
let currentTaxRate   = 0;

function onInvoiceChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) {
        document.getElementById('invoice-info-card').style.display = 'none';
        document.getElementById('cn-calc-box').style.display = 'none';
        currentRemaining = 0; currentTaxRate = 0;
        return;
    }
    currentRemaining = parseFloat(opt.dataset.remaining) || 0;
    currentTaxRate   = parseFloat(opt.dataset.taxRate)   || 0;

    document.getElementById('inv-number').textContent    = opt.dataset.number;
    document.getElementById('inv-remaining').textContent = fmt(currentRemaining);
    document.getElementById('inv-tax-rate').textContent  = currentTaxRate.toFixed(2) + '%';
    document.getElementById('invoice-info-card').style.display = '';

    recalc();
}

function recalc() {
    const amtField = document.getElementById('amount');
    const amt      = parseFloat(amtField.value) || 0;
    if (amt <= 0) {
        document.getElementById('cn-calc-box').style.display = 'none';
        return;
    }
    const tax   = round2(amt * currentTaxRate / 100);
    const total = round2(amt + tax);
    const after = round2(Math.max(0, currentRemaining - total));

    document.getElementById('calc-amount').textContent   = fmt(amt);
    document.getElementById('calc-tax-rate').textContent = currentTaxRate.toFixed(2);
    document.getElementById('calc-tax').textContent      = fmt(tax);
    document.getElementById('calc-total').textContent    = fmt(total);
    document.getElementById('calc-after').textContent    = fmt(after);

    document.getElementById('calc-tax-row').style.display = currentTaxRate > 0 ? '' : 'none';
    document.getElementById('cn-calc-box').style.display  = '';
}

function fillMax() {
    if (currentRemaining <= 0 || currentTaxRate < 0) return;
    // Back-calculate base from remaining (which is inclusive of tax)
    let maxBase;
    if (currentTaxRate > 0) {
        maxBase = round2(currentRemaining * 100 / (100 + currentTaxRate));
    } else {
        maxBase = currentRemaining;
    }
    document.getElementById('amount').value = maxBase.toFixed(2);
    recalc();
}

function fmt(n)    { return n.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2}); }
function round2(n) { return Math.round(n * 100) / 100; }

// Trigger on page load if invoice is preselected
document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('invoice_id');
    if (sel && sel.value) onInvoiceChange(sel);
});
</script>
@endpush

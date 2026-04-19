@extends('accounting._layout')
@section('title', 'قيد متكرر جديد')

@section('topbar-actions')
    <a href="{{ route('accounting.recurring.index') }}" class="ac-btn ac-btn--secondary ac-btn--sm">
        رجوع للقيود المتكررة
    </a>
@endsection

@section('content')

<div class="ac-rec-create">
    <section class="ac-rec-hero">
        <div class="ac-rec-hero__copy">
            <span class="ac-rec-hero__eyebrow">تشغيل تلقائي</span>
            <h2 class="ac-rec-hero__title">قيد متكرر جديد</h2>
            <p class="ac-rec-hero__text">
                استخدمه للمصروفات أو الإيرادات الثابتة مثل الإيجار والاشتراكات والرواتب، وسيقوم النظام بتوليد القيد في موعده.
            </p>
        </div>
        <div class="ac-rec-hero__card">
            <span>Recurring</span>
            <strong>جاهز للتوليد التلقائي</strong>
        </div>
    </section>

    <div class="ac-rec-layout">
        <section class="ac-rec-main">

<div class="ac-card ac-rec-card">
    <div class="ac-card__header">
        <h3 class="ac-card__title">بيانات القيد المتكرر</h3>
    </div>

    <form method="POST" action="{{ route('accounting.recurring.store') }}">
        @csrf

        {{-- Header --}}
        <div class="ac-rec-meta-grid">
            <div class="ac-form-group">
                <label class="ac-label">الوصف <span class="ac-required">*</span></label>
                <input type="text" name="description" value="{{ old('description') }}"
                       class="ac-input @error('description') ac-input--error @enderror"
                       placeholder="مثال: إيجار المكتب الشهري">
                @error('description')<p class="ac-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="ac-form-group">
                <label class="ac-label">التكرار <span class="ac-required">*</span></label>
                <select name="frequency" class="ac-select @error('frequency') ac-input--error @enderror">
                    <option value="">— اختر —</option>
                    <option value="daily"     {{ old('frequency') === 'daily'     ? 'selected' : '' }}>يومي</option>
                    <option value="weekly"    {{ old('frequency') === 'weekly'    ? 'selected' : '' }}>أسبوعي</option>
                    <option value="monthly"   {{ old('frequency') === 'monthly'   ? 'selected' : '' }}>شهري</option>
                    <option value="quarterly" {{ old('frequency') === 'quarterly' ? 'selected' : '' }}>ربع سنوي</option>
                    <option value="yearly"    {{ old('frequency') === 'yearly'    ? 'selected' : '' }}>سنوي</option>
                </select>
                @error('frequency')<p class="ac-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="ac-form-group">
                <label class="ac-label">تاريخ البداية <span class="ac-required">*</span></label>
                <input type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}"
                       class="ac-input @error('start_date') ac-input--error @enderror">
                @error('start_date')<p class="ac-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="ac-form-group">
                <label class="ac-label">تاريخ الانتهاء (اختياري)</label>
                <input type="date" name="end_date" value="{{ old('end_date') }}"
                       class="ac-input @error('end_date') ac-input--error @enderror">
                <p class="ac-rec-field-hint">اتركه فارغاً للتكرار بلا نهاية</p>
                @error('end_date')<p class="ac-field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Lines --}}
        <div class="ac-rec-lines-section">
            <label class="ac-label ac-rec-lines-title">سطور القيد المحاسبي</label>
            @error('lines')<p class="ac-field-error ac-rec-lines-error">{{ $message }}</p>@enderror

            {{-- Header row --}}
            <div class="ac-rec-line-head">
                <span>الحساب</span>
                <span>نوع (مدين/دائن)</span>
                <span>المبلغ</span>
                <span>الوصف (اختياري)</span>
                <span></span>
            </div>

            <div id="linesContainer">
                @foreach(old('lines', [['account_id'=>'','type'=>'debit','amount'=>'','description'=>''],['account_id'=>'','type'=>'credit','amount'=>'','description'=>'']]) as $i => $line)
                <div class="ac-jl-row ac-rec-line-row">
                    <select name="lines[{{ $i }}][account_id]" class="ac-select">
                        <option value="">— اختر حساباً —</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ old("lines.{$i}.account_id") == $acc->id ? 'selected' : '' }}>
                                {{ $acc->code }} - {{ $acc->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="lines[{{ $i }}][type]" class="ac-select">
                        <option value="debit"  {{ ($line['type'] ?? 'debit')  === 'debit'  ? 'selected' : '' }}>مدين</option>
                        <option value="credit" {{ ($line['type'] ?? '')        === 'credit' ? 'selected' : '' }}>دائن</option>
                    </select>
                    <input type="number" name="lines[{{ $i }}][amount]"
                           value="{{ $line['amount'] ?? '' }}"
                           class="ac-input" min="0.01" step="0.01" placeholder="0.00">
                    <input type="text" name="lines[{{ $i }}][description]"
                           value="{{ $line['description'] ?? '' }}"
                           class="ac-input" placeholder="وصف السطر">
                    @if($i > 1)
                    <button type="button" class="ac-btn ac-btn--danger ac-btn--sm"
                            onclick="this.closest('.ac-jl-row').remove();calcTotals()">×</button>
                    @else
                    <div class="ac-rec-line-spacer"></div>
                    @endif
                </div>
                @endforeach
            </div>

            {{-- Totals --}}
            <div class="ac-rec-totals">
                <span>مجموع المدين: <strong id="totalDebit">0.00</strong></span>
                <span>مجموع الدائن: <strong id="totalCredit">0.00</strong></span>
                <span id="balanceStatus" class="ac-rec-balance-status"></span>
            </div>

            <button type="button" class="ac-btn ac-btn--ghost ac-btn--sm ac-rec-add-line" onclick="addLine()">
                + إضافة سطر
            </button>
        </div>

        <div class="ac-rec-actions">
            <button type="submit" class="ac-btn ac-btn--primary">حفظ القيد المتكرر</button>
            <a href="{{ route('accounting.recurring.index') }}" class="ac-btn ac-btn--ghost">إلغاء</a>
        </div>
    </form>
</div>
</section>

<aside class="ac-rec-side">
    <div class="ac-rec-summary-card">
        <div class="ac-rec-summary-card__icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="17,1 21,5 17,9"/>
                <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                <polyline points="7,23 3,19 7,15"/>
                <path d="M21 13v2a4 4 0 0 1-4 4H3"/>
            </svg>
        </div>
        <h3>تأكد قبل الحفظ</h3>
        <p>القيد المتكرر لن يكون صحيحًا إلا إذا كان مجموع المدين مساويًا لمجموع الدائن.</p>
        <div class="ac-rec-summary-card__tips">
            <span>اختر الحسابات بعناية</span>
            <span>حدد تاريخ البداية بدقة</span>
            <span>اترك النهاية فارغة للتكرار المستمر</span>
        </div>
    </div>
</aside>
</div>
</div>

@push('scripts')
<script>
const accountOptions = `@foreach($accounts as $acc)<option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>@endforeach`;
let lineIdx = {{ count(old('lines', [[],[]])) }};

function addLine() {
    const row = document.createElement('div');
    row.className = 'ac-jl-row ac-rec-line-row';
    row.innerHTML = `
        <select name="lines[${lineIdx}][account_id]" class="ac-select">
            <option value="">— اختر حساباً —</option>${accountOptions}
        </select>
        <select name="lines[${lineIdx}][type]" class="ac-select">
            <option value="debit">مدين</option>
            <option value="credit">دائن</option>
        </select>
        <input type="number" name="lines[${lineIdx}][amount]" class="ac-input" min="0.01" step="0.01" placeholder="0.00">
        <input type="text" name="lines[${lineIdx}][description]" class="ac-input" placeholder="وصف السطر">
        <button type="button" class="ac-btn ac-btn--danger ac-btn--sm" onclick="this.closest('.ac-jl-row').remove();calcTotals()">×</button>
    `;
    document.getElementById('linesContainer').appendChild(row);
    lineIdx++;
    row.querySelectorAll('input[type="number"]').forEach(i => i.addEventListener('input', calcTotals));
    row.querySelectorAll('select').forEach(s => s.addEventListener('change', calcTotals));
}

function calcTotals() {
    let debit = 0, credit = 0;
    document.querySelectorAll('.ac-jl-row').forEach(row => {
        const type   = row.querySelector('select[name*="[type]"]')?.value;
        const amount = parseFloat(row.querySelector('input[type="number"]')?.value) || 0;
        if (type === 'debit')  debit  += amount;
        if (type === 'credit') credit += amount;
    });
    document.getElementById('totalDebit').textContent  = debit.toFixed(2);
    document.getElementById('totalCredit').textContent = credit.toFixed(2);
    const el = document.getElementById('balanceStatus');
    if (Math.abs(debit - credit) < 0.01 && debit > 0) {
        el.textContent = '✓ متوازن';
        el.classList.add('is-balanced');
        el.classList.remove('is-unbalanced');
    } else {
        el.textContent = '× غير متوازن';
        el.classList.add('is-unbalanced');
        el.classList.remove('is-balanced');
    }
}

document.querySelectorAll('#linesContainer input[type="number"], #linesContainer select').forEach(el => {
    el.addEventListener('input', calcTotals);
    el.addEventListener('change', calcTotals);
});
calcTotals();
</script>
@endpush

@endsection

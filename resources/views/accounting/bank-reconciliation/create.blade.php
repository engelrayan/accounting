@extends('accounting._layout')

@section('title', 'كشف بنكي جديد')

@section('content')

<form method="POST" action="{{ route('accounting.bank-reconciliation.store') }}" id="br-create-form">
@csrf

{{-- ── Header card ── --}}
<div class="ac-card" style="margin-bottom:1.25rem">
    <div class="ac-card__header">
        <h2 class="ac-card__title">بيانات الكشف البنكي</h2>
    </div>
    <div class="ac-card__body">

        @if($errors->has('bank_statement'))
            <div class="ac-alert ac-alert--danger" style="margin-bottom:1rem">
                {{ $errors->first('bank_statement') }}
            </div>
        @endif

        <div class="ac-form-grid ac-form-grid--3">

            <div class="ac-form-group">
                <label class="ac-label ac-label--required">الحساب البنكي</label>
                <select name="account_id" class="ac-select" required>
                    <option value="">— اختر الحساب —</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}"
                            {{ old('account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->code }} — {{ $account->name }}
                        </option>
                    @endforeach
                </select>
                @error('account_id')
                    <span class="ac-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="ac-form-group">
                <label class="ac-label ac-label--required">تاريخ الكشف</label>
                <input type="date" name="statement_date" class="ac-input"
                       value="{{ old('statement_date', date('Y-m-d')) }}" required>
                @error('statement_date')
                    <span class="ac-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="ac-form-group"></div>{{-- spacer --}}

            <div class="ac-form-group">
                <label class="ac-label ac-label--required">الرصيد الافتتاحي</label>
                <input type="number" name="opening_balance" class="ac-input"
                       step="0.01" value="{{ old('opening_balance', '0.00') }}" required>
                @error('opening_balance')
                    <span class="ac-field-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="ac-form-group">
                <label class="ac-label ac-label--required">الرصيد الختامي</label>
                <input type="number" name="closing_balance" class="ac-input"
                       step="0.01" value="{{ old('closing_balance', '0.00') }}" required>
                @error('closing_balance')
                    <span class="ac-field-error">{{ $message }}</span>
                @enderror
            </div>

        </div>
    </div>
</div>

{{-- ── Lines card ── --}}
<div class="ac-card" style="margin-bottom:1.25rem">
    <div class="ac-card__header" style="display:flex;align-items:center;justify-content:space-between">
        <h2 class="ac-card__title">بنود الكشف البنكي</h2>
        <button type="button" id="br-add-line" class="ac-btn ac-btn--ghost ac-btn--sm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            إضافة سطر
        </button>
    </div>
    <div class="ac-card__body" style="padding:0">

        @error('lines')
            <div class="ac-alert ac-alert--danger" style="margin:.75rem 1.25rem">
                {{ $message }}
            </div>
        @enderror

        <div class="ac-table-wrap">
            <table class="ac-table" id="br-lines-table">
                <thead>
                    <tr>
                        <th style="width:130px">التاريخ</th>
                        <th>الوصف / البيان</th>
                        <th style="width:140px;text-align:left">مدين (خروج)</th>
                        <th style="width:140px;text-align:left">دائن (دخول)</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody id="br-lines-body">
                    @php $oldLines = old('lines', [[]]); @endphp
                    @foreach($oldLines as $i => $line)
                    <tr class="br-line-row">
                        <td>
                            <input type="date" name="lines[{{ $i }}][transaction_date]"
                                   class="ac-input ac-input--sm"
                                   value="{{ $line['transaction_date'] ?? date('Y-m-d') }}" required>
                        </td>
                        <td>
                            <input type="text" name="lines[{{ $i }}][description]"
                                   class="ac-input ac-input--sm"
                                   placeholder="بيان المعاملة"
                                   value="{{ $line['description'] ?? '' }}" required>
                        </td>
                        <td>
                            <input type="number" name="lines[{{ $i }}][debit]"
                                   class="ac-input ac-input--sm"
                                   step="0.01" min="0"
                                   value="{{ $line['debit'] ?? '' }}"
                                   placeholder="0.00">
                        </td>
                        <td>
                            <input type="number" name="lines[{{ $i }}][credit]"
                                   class="ac-input ac-input--sm"
                                   step="0.01" min="0"
                                   value="{{ $line['credit'] ?? '' }}"
                                   placeholder="0.00">
                        </td>
                        <td>
                            <button type="button" class="ac-btn ac-btn--ghost ac-btn--icon br-remove-line"
                                    title="حذف السطر">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     width="14" height="14">
                                    <line x1="18" y1="6" x2="6" y2="18"/>
                                    <line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="ac-table-total">
                        <td colspan="2" style="text-align:right;font-weight:600">الإجمالي</td>
                        <td id="br-total-debit"  style="text-align:left;font-weight:700">0.00</td>
                        <td id="br-total-credit" style="text-align:left;font-weight:700">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- ── Actions ── --}}
<div style="display:flex;gap:.75rem;justify-content:flex-end">
    <a href="{{ route('accounting.bank-reconciliation.index') }}" class="ac-btn ac-btn--ghost">
        إلغاء
    </a>
    <button type="submit" class="ac-btn ac-btn--primary">
        حفظ وبدء المطابقة
    </button>
</div>

</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => BrCreate.init());

const BrCreate = {
    lineIndex: {{ count(old('lines', [[]])) }},

    init() {
        document.getElementById('br-add-line').addEventListener('click', () => this.addLine());

        document.addEventListener('click', e => {
            if (e.target.closest('.br-remove-line')) {
                const row = e.target.closest('.br-line-row');
                if (document.querySelectorAll('.br-line-row').length > 1) {
                    row.remove();
                    this.reindex();
                    this.updateTotals();
                }
            }
        });

        document.addEventListener('input', e => {
            if (e.target.closest('#br-lines-body')) this.updateTotals();
        });

        this.updateTotals();
    },

    addLine() {
        const i = this.lineIndex++;
        const today = new Date().toISOString().slice(0, 10);
        const tr = document.createElement('tr');
        tr.className = 'br-line-row';
        tr.innerHTML = `
            <td><input type="date" name="lines[${i}][transaction_date]"
                       class="ac-input ac-input--sm" value="${today}" required></td>
            <td><input type="text" name="lines[${i}][description]"
                       class="ac-input ac-input--sm" placeholder="بيان المعاملة" required></td>
            <td><input type="number" name="lines[${i}][debit]"
                       class="ac-input ac-input--sm" step="0.01" min="0" placeholder="0.00"></td>
            <td><input type="number" name="lines[${i}][credit]"
                       class="ac-input ac-input--sm" step="0.01" min="0" placeholder="0.00"></td>
            <td>
                <button type="button" class="ac-btn ac-btn--ghost ac-btn--icon br-remove-line" title="حذف">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </td>`;
        document.getElementById('br-lines-body').appendChild(tr);
        tr.querySelector('input[type="text"]').focus();
    },

    reindex() {
        document.querySelectorAll('.br-line-row').forEach((row, i) => {
            row.querySelectorAll('input').forEach(input => {
                input.name = input.name.replace(/lines\[\d+\]/, `lines[${i}]`);
            });
        });
        this.lineIndex = document.querySelectorAll('.br-line-row').length;
    },

    updateTotals() {
        let debit = 0, credit = 0;
        document.querySelectorAll('.br-line-row').forEach(row => {
            debit  += parseFloat(row.querySelector('[name*="[debit]"]')?.value  || 0);
            credit += parseFloat(row.querySelector('[name*="[credit]"]')?.value || 0);
        });
        document.getElementById('br-total-debit').textContent  = debit.toFixed(2);
        document.getElementById('br-total-credit').textContent = credit.toFixed(2);
    },
};
</script>
@endpush

@extends('accounting._layout')

@section('title', $partner->name)

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">{{ $partner->name }}</h1>
    <div class="ac-page-header__actions">
        <a href="{{ route('accounting.partners.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
    </div>
</div>

@if($errors->has('add_capital'))
    <div class="ac-alert ac-alert--error">{{ $errors->first('add_capital') }}</div>
@endif
@if($errors->has('withdraw'))
    <div class="ac-alert ac-alert--error">{{ $errors->first('withdraw') }}</div>
@endif

{{-- ── Summary cards ── --}}
<div class="ac-dash-grid">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">رأس المال</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($capital, 2) }}</div>
        <div class="ac-dash-card__footer">إجمالي ما أضافه من رأس مال</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">المسحوبات</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23,18 13.5,8.5 8.5,13.5 1,6"/>
                    <polyline points="17,18 23,18 23,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-danger">{{ number_format($drawings, 2) }}</div>
        <div class="ac-dash-card__footer">إجمالي ما سحبه من رصيد</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">صافي الرصيد</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($balance, 2) }}</div>
        <div class="ac-dash-card__footer">رأس المال بعد خصم المسحوبات</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">نسبة الشراكة</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12,6 12,12 16,14"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $percentage }}%</div>
        <div class="ac-dash-card__footer">من إجمالي رأس مال الشركة</div>
    </div>

</div>

{{-- ── Action panels ── --}}
<div class="ac-partner-actions">

    {{-- إضافة رأس مال --}}
    <div class="ac-card">
        <div class="ac-card__body">
            <p class="ac-section-label">إضافة رأس مال</p>
            <form method="POST" action="{{ route('accounting.partners.add-capital', $partner) }}">
                @csrf
                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="cap_amount">المبلغ</label>
                        <input type="number" step="0.01" min="0.01"
                               id="cap_amount" name="amount"
                               class="ac-control ac-control--num {{ $errors->has('amount') && old('_action') === 'capital' ? 'ac-control--error' : '' }}"
                               placeholder="0.00" required>
                    </div>
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="cap_cash">الحساب النقدي</label>
                        <select id="cap_cash" name="cash_account_id" class="ac-select" required>
                            <option value="">— اختر الحساب —</option>
                            @foreach($cashAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="cap_date">التاريخ</label>
                        <input type="date" id="cap_date" name="date"
                               value="{{ today()->toDateString() }}"
                               class="ac-control" required>
                    </div>
                    <div class="ac-form-group">
                        <label class="ac-label" for="cap_desc">ملاحظة</label>
                        <input type="text" id="cap_desc" name="description"
                               class="ac-control" placeholder="اختياري">
                    </div>
                </div>
                <button type="submit" class="ac-btn ac-btn--primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    إضافة رأس مال
                </button>
            </form>
        </div>
    </div>

    {{-- سحب --}}
    <div class="ac-card">
        <div class="ac-card__body">
            <p class="ac-section-label">تسجيل سحب</p>
            <form method="POST" action="{{ route('accounting.partners.withdraw', $partner) }}">
                @csrf
                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="wd_amount">المبلغ</label>
                        <input type="number" step="0.01" min="0.01"
                               id="wd_amount" name="amount"
                               class="ac-control ac-control--num"
                               placeholder="0.00" required>
                    </div>
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="wd_cash">الحساب النقدي</label>
                        <select id="wd_cash" name="cash_account_id" class="ac-select" required>
                            <option value="">— اختر الحساب —</option>
                            @foreach($cashAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="wd_date">التاريخ</label>
                        <input type="date" id="wd_date" name="date"
                               value="{{ today()->toDateString() }}"
                               class="ac-control" required>
                    </div>
                    <div class="ac-form-group">
                        <label class="ac-label" for="wd_desc">ملاحظة</label>
                        <input type="text" id="wd_desc" name="description"
                               class="ac-control" placeholder="اختياري">
                    </div>
                </div>
                <button type="submit" class="ac-btn ac-btn--danger">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    تسجيل سحب
                </button>
            </form>
        </div>
    </div>

</div>

{{-- ── Partner info ── --}}
<div class="ac-card ac-mt-4">
    <div class="ac-entry-meta">
        @if($partner->phone)
        <div class="ac-entry-meta__item">
            <div class="ac-entry-meta__label">الهاتف</div>
            <div class="ac-entry-meta__value">{{ $partner->phone }}</div>
        </div>
        @endif
        @if($partner->email)
        <div class="ac-entry-meta__item">
            <div class="ac-entry-meta__label">البريد الإلكتروني</div>
            <div class="ac-entry-meta__value">{{ $partner->email }}</div>
        </div>
        @endif
        <div class="ac-entry-meta__item">
            <div class="ac-entry-meta__label">حساب رأس المال</div>
            <div class="ac-entry-meta__value ac-text-mono">
                {{ $partner->capitalAccount->code }} — {{ $partner->capitalAccount->name }}
            </div>
        </div>
        <div class="ac-entry-meta__item">
            <div class="ac-entry-meta__label">حساب المسحوبات</div>
            <div class="ac-entry-meta__value ac-text-mono">
                {{ $partner->drawingAccount->code }} — {{ $partner->drawingAccount->name }}
            </div>
        </div>
    </div>
    @if($partner->notes)
    <div class="ac-card__body">
        <p class="ac-section-label">ملاحظات</p>
        <p class="ac-text-sm">{{ $partner->notes }}</p>
    </div>
    @endif
</div>

{{-- ── Transaction ledger ── --}}
<h2 class="ac-section-title ac-mt-4">سجل العمليات</h2>

<div class="ac-table-wrap">
    @if($ledger->isEmpty())
        <div class="ac-empty-state ac-empty-state--sm ac-text-muted">
            لا توجد عمليات مسجلة بعد.
        </div>
    @else
        <table class="ac-table">
            <thead>
                <tr>
                    <th>رقم القيد</th>
                    <th>التاريخ</th>
                    <th>الوصف</th>
                    <th>النوع</th>
                    <th class="ac-col-num">المبلغ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($ledger as $entry)
                    @foreach($entry->lines as $line)
                    <tr>
                        <td class="ac-text-mono">{{ $entry->entry_number }}</td>
                        <td>{{ \Carbon\Carbon::parse($entry->entry_date)->format('Y/m/d') }}</td>
                        <td>{{ $entry->description }}</td>
                        <td>
                            @if($line->account_id === $partner->capital_account_id)
                                @if($line->credit > 0)
                                    <span class="ac-badge ac-badge--posted">إضافة رأس مال</span>
                                @else
                                    <span class="ac-badge ac-badge--reversed">خصم رأس مال</span>
                                @endif
                            @else
                                <span class="ac-badge ac-badge--draft">سحب</span>
                            @endif
                        </td>
                        <td class="ac-col-num">
                            {{ number_format(max($line->debit, $line->credit), 2) }}
                        </td>
                        <td>
                            <a href="{{ route('accounting.journal-entries.show', $entry) }}"
                               class="ac-btn ac-btn--ghost ac-btn--sm">عرض</a>
                        </td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@endsection

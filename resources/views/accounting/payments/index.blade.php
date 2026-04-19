@extends('accounting._layout')

@section('title', 'المدفوعات')

@section('content')

{{-- ── Totals bar ─────────────────────────────────────────────────────────── --}}
<div class="ac-dash-grid">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي المحصّل</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-success">{{ number_format($totalAll, 2) }}</div>
        <div class="ac-dash-card__footer">
            @if($method || $from || $to) نتيجة الفلتر الحالي @else جميع الدفعات @endif
        </div>
    </div>

    @foreach([
        ['cash',     '💵', 'نقداً'],
        ['bank',     '🏦', 'تحويل بنكي'],
        ['wallet',   '📱', 'محفظة'],
        ['instapay', '⚡', 'إنستاباي'],
    ] as [$m, $icon, $label])
    @if(isset($methodCounts[$m]) && $methodCounts[$m]->total > 0)
    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">{{ $icon }} {{ $label }}</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <span style="font-size:20px;">{{ $icon }}</span>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($methodCounts[$m]->sum_amount, 2) }}</div>
        <div class="ac-dash-card__footer">{{ $methodCounts[$m]->total }} دفعة</div>
    </div>
    @endif
    @endforeach

</div>

{{-- ── Filter bar ──────────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('accounting.payments.index') }}" class="ac-pay-filter">
    <div class="ac-form-row">
        <div class="ac-form-group">
            <label class="ac-label" for="filter_method">طريقة الدفع</label>
            <select id="filter_method" name="method" class="ac-select">
                <option value="">الكل</option>
                @foreach([
                    ['cash','💵 نقداً'],['bank','🏦 بنكي'],
                    ['wallet','📱 محفظة'],['instapay','⚡ إنستاباي'],
                    ['cheque','📝 شيك'],['card','💳 بطاقة'],['other','أخرى'],
                ] as [$val, $lbl])
                    <option value="{{ $val }}" {{ $method === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="ac-form-group">
            <label class="ac-label" for="filter_from">من تاريخ</label>
            <input type="date" id="filter_from" name="from" class="ac-control" value="{{ $from }}">
        </div>
        <div class="ac-form-group">
            <label class="ac-label" for="filter_to">إلى تاريخ</label>
            <input type="date" id="filter_to" name="to" class="ac-control" value="{{ $to }}">
        </div>
        <div class="ac-form-group ac-pay-filter__actions">
            <button type="submit" class="ac-btn ac-btn--primary">فلتر</button>
            <a href="{{ route('accounting.payments.index') }}" class="ac-btn ac-btn--secondary">إعادة ضبط</a>
        </div>
    </div>
</form>

{{-- ── Payments table ──────────────────────────────────────────────────────── --}}
@if($payments->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
        </svg>
        <p>لا توجد دفعات {{ $method || $from || $to ? 'تطابق الفلتر' : 'بعد' }}.</p>
    </div>
@else

<div class="ac-card">
    <div class="ac-card__body" style="padding:0;">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>العميل</th>
                    <th>الفاتورة</th>
                    <th class="ac-table__num">المبلغ</th>
                    <th>طريقة الدفع</th>
                    <th>تاريخ السداد</th>
                    <th>ملاحظات</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $pay)
                <tr>
                    <td>
                        <div class="ac-cust-cell">
                            <div class="ac-cust-avatar ac-cust-avatar--sm">
                                {{ mb_substr($pay->customer->name, 0, 1) }}
                            </div>
                            <span>{{ $pay->customer->name }}</span>
                        </div>
                    </td>
                    <td class="ac-text-mono">
                        <a href="{{ route('accounting.invoices.show', $pay->invoice_id) }}"
                           class="ac-link">
                            {{ $pay->invoice->invoice_number }}
                        </a>
                    </td>
                    <td class="ac-table__num ac-font-bold ac-text-success">
                        {{ number_format($pay->amount, 2) }}
                    </td>
                    <td>
                        <span class="ac-pay-method-pill ac-pay-method-pill--{{ $pay->payment_method }}">
                            {{ \App\Modules\Accounting\Models\Payment::methodIcon($pay->payment_method) }}
                            {{ \App\Modules\Accounting\Models\Payment::methodLabel($pay->payment_method) }}
                        </span>
                    </td>
                    <td class="ac-table__muted">{{ $pay->payment_date->format('Y-m-d') }}</td>
                    <td class="ac-table__muted">{{ $pay->notes ?: '—' }}</td>
                    <td class="ac-row-actions">
                        <a href="{{ route('accounting.invoices.show', $pay->invoice_id) }}"
                           class="ac-btn ac-btn--ghost ac-btn--sm">الفاتورة</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($payments->hasPages())
    <div class="ac-pagination">{{ $payments->links() }}</div>
@endif

@endif
@endsection

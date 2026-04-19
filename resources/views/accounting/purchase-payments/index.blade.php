@extends('accounting._layout')

@section('title', 'مدفوعات الموردين')

@section('content')

{{-- ── Filter Bar ───────────────────────────────────────────────────────── --}}
<div class="ac-filter-bar" style="margin-bottom:16px">
    <form method="GET" action="{{ route('accounting.purchase-payments.index') }}" class="ac-report-filter">
        <div class="ac-form-row" style="align-items:flex-end">
            <div class="ac-form-group">
                <label class="ac-label" for="filter_from">من تاريخ</label>
                <input type="date" id="filter_from" name="from" class="ac-control"
                       value="{{ $from }}">
            </div>
            <div class="ac-form-group">
                <label class="ac-label" for="filter_to">إلى تاريخ</label>
                <input type="date" id="filter_to" name="to" class="ac-control"
                       value="{{ $to }}">
            </div>
            <div class="ac-form-group">
                <label class="ac-label" for="filter_method">طريقة الدفع</label>
                <select id="filter_method" name="method" class="ac-select">
                    <option value="">الكل</option>
                    @foreach(['cash' => 'نقداً', 'bank' => 'بنكي', 'wallet' => 'محفظة', 'instapay' => 'إنستاباي', 'cheque' => 'شيك', 'card' => 'بطاقة', 'other' => 'أخرى'] as $val => $label)
                    <option value="{{ $val }}" {{ $method === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ac-form-group ac-form-group--end">
                <button type="submit" class="ac-btn ac-btn--primary">تصفية</button>
                <a href="{{ route('accounting.purchase-payments.index') }}" class="ac-btn ac-btn--secondary">إعادة تعيين</a>
            </div>
        </div>
    </form>
</div>

{{-- ── Method totals ────────────────────────────────────────────────────── --}}
@if($methodTotals->isNotEmpty())
<div class="ac-dash-grid ac-dash-grid--wrap" style="margin-bottom:20px">
    @foreach($methodTotals as $m => $mt)
    <div class="ac-dash-card ac-dash-card--sm">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">
                {{ \App\Modules\Accounting\Models\PurchasePayment::methodIcon($m) }}
                {{ \App\Modules\Accounting\Models\PurchasePayment::methodLabel($m) }}
            </span>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($mt->total, 2) }}</div>
        <div class="ac-dash-card__footer">{{ $mt->cnt }} دفعة</div>
    </div>
    @endforeach
    <div class="ac-dash-card ac-dash-card--sm ac-dash-card--total">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">الإجمالي</span>
        </div>
        <div class="ac-dash-card__amount ac-text-danger">{{ number_format($totalAmount, 2) }}</div>
        <div class="ac-dash-card__footer">{{ $payments->total() }} دفعة</div>
    </div>
</div>
@endif

{{-- ── Table ────────────────────────────────────────────────────────────── --}}
@if($payments->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="1" y="4" width="22" height="16" rx="2"/>
            <line x1="1" y1="10" x2="23" y2="10"/>
        </svg>
        <div>
            <p style="font-weight:700;margin:0">لا توجد مدفوعات</p>
            <p style="font-size:13px;color:var(--ac-text-muted);margin:4px 0 0">
                سجِّل دفعات من خلال صفحة المورد أو الفاتورة.
            </p>
        </div>
    </div>
@else
    <div class="ac-card">
        <div style="overflow-x:auto">
            <table class="ac-table">
                <thead>
                    <tr>
                        <th>تاريخ الدفع</th>
                        <th>المورد</th>
                        <th>الفاتورة</th>
                        <th>طريقة الدفع</th>
                        <th class="ac-table__num">المبلغ</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                    <tr>
                        <td class="ac-table__muted">{{ \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') }}</td>
                        <td>
                            @if($payment->vendor)
                                <a href="{{ route('accounting.vendors.show', $payment->vendor_id) }}"
                                   class="ac-table-link">{{ $payment->vendor->name }}</a>
                            @else
                                <span class="ac-table__muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->purchaseInvoice)
                                <a href="{{ route('accounting.purchase-invoices.show', $payment->purchase_invoice_id) }}"
                                   class="ac-text-mono ac-table-link">
                                    {{ $payment->purchaseInvoice->invoice_number }}
                                </a>
                            @else
                                <span class="ac-table__muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="ac-pay-method-pill ac-pay-method-pill--{{ $payment->payment_method }}">
                                {{ \App\Modules\Accounting\Models\PurchasePayment::methodIcon($payment->payment_method) }}
                                {{ \App\Modules\Accounting\Models\PurchasePayment::methodLabel($payment->payment_method) }}
                            </span>
                        </td>
                        <td class="ac-table__num ac-font-bold ac-text-danger">
                            −{{ number_format($payment->amount, 2) }}
                        </td>
                        <td class="ac-table__muted">{{ $payment->notes ?: '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="ac-table__foot-total">
                        <td colspan="4" class="ac-font-bold">إجمالي الصفحة</td>
                        <td class="ac-table__num ac-font-bold ac-text-danger">
                            −{{ number_format($payments->sum('amount'), 2) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div style="margin-top:16px">
        {{ $payments->links() }}
    </div>
@endif

@endsection

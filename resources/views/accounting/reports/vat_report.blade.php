@extends('accounting._layout')

@section('title', 'تقرير ضريبة القيمة المضافة')

@php
$fmt       = fn(float $n) => number_format($n, 2);
$fromHuman = \Carbon\Carbon::parse($from)->locale('ar')->translatedFormat('j F Y');
$toHuman   = \Carbon\Carbon::parse($to)->locale('ar')->translatedFormat('j F Y');

$statusLabels = [
    'paid'      => ['text' => 'مدفوعة',  'mod' => 'posted'],
    'partial'   => ['text' => 'جزئي',    'mod' => 'draft'],
    'pending'   => ['text' => 'معلقة',   'mod' => 'pending'],
    'cancelled' => ['text' => 'ملغاة',   'mod' => 'reversed'],
];
$statusLabel = fn($s) => $statusLabels[$s]['text'] ?? $s;
$statusMod   = fn($s) => $statusLabels[$s]['mod']  ?? 'pending';
@endphp

@section('topbar-actions')
    <div class="ac-topbar-actions">
        <a href="{{ route('accounting.invoices.index') }}"
           class="ac-btn ac-btn--secondary ac-btn--sm">فواتير المبيعات</a>
        <a href="{{ route('accounting.purchase-invoices.index') }}"
           class="ac-btn ac-btn--secondary ac-btn--sm">فواتير المشتريات</a>
    </div>
@endsection

@section('content')

{{-- ── Hero ─────────────────────────────────────────────────────────────── --}}
<div class="ac-vat-hero">
    <div class="ac-vat-hero__content">
        <span class="ac-vat-hero__eyebrow">الضريبة</span>
        <h2 class="ac-vat-hero__title">تقرير ضريبة القيمة المضافة — VAT Report</h2>
        <p class="ac-vat-hero__text">
            ملخص ضريبة المخرجات (المبيعات) وضريبة المدخلات (المشتريات) وصافي الضريبة المستحقة للفترة.
        </p>
    </div>
    <div class="ac-vat-hero__meta">
        <span class="ac-vat-hero__label">الفترة</span>
        <strong class="ac-vat-hero__date">{{ $fromHuman }} — {{ $toHuman }}</strong>
    </div>
</div>

{{-- ── Filter ───────────────────────────────────────────────────────────── --}}
<div class="ac-card ac-report-filter-card">
    <div class="ac-card__body">
        <form method="GET" action="{{ route('accounting.reports.vat-report') }}" class="ac-report-filter">
            <div class="ac-form-row">
                <div class="ac-form-group">
                    <label class="ac-label" for="from">من تاريخ</label>
                    <input id="from" name="from" type="date" class="ac-control" value="{{ $from }}">
                </div>
                <div class="ac-form-group">
                    <label class="ac-label" for="to">إلى تاريخ</label>
                    <input id="to" name="to" type="date" class="ac-control" value="{{ $to }}">
                </div>
                <div class="ac-form-group ac-form-group--end">
                    <button type="submit" class="ac-btn ac-btn--primary">تطبيق</button>
                    <a href="{{ route('accounting.reports.vat-report') }}"
                       class="ac-btn ac-btn--secondary">هذا الشهر</a>
                    <a href="{{ route('accounting.reports.vat-report', ['from' => $from, 'to' => $to, 'export' => 'pdf']) }}"
                       class="ac-btn ac-btn--secondary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" style="margin-left:4px">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                            <polyline points="7,10 12,15 17,10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        تصدير PDF
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── Summary cards ────────────────────────────────────────────────────── --}}
<div class="ac-dash-grid ac-dash-grid--3" style="margin-bottom:28px">

    {{-- ضريبة المخرجات --}}
    <div class="ac-dash-card ac-vat-card ac-vat-card--output">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">ضريبة المخرجات (مبيعات)</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"/>
                    <polyline points="17,6 23,6 23,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $fmt($outputVat) }}</div>
        <div class="ac-dash-card__footer">{{ $salesInvoices->count() }} فاتورة مبيعات</div>
    </div>

    {{-- ضريبة المدخلات --}}
    <div class="ac-dash-card ac-vat-card ac-vat-card--input">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">ضريبة المدخلات (مشتريات)</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23,18 13.5,8.5 8.5,13.5 1,6"/>
                    <polyline points="17,18 23,18 23,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $fmt($inputVat) }}</div>
        <div class="ac-dash-card__footer">{{ $purchaseInvoices->count() }} فاتورة مشتريات</div>
    </div>

    {{-- صافي الضريبة --}}
    @php $netPositive = $netVat >= 0; @endphp
    <div class="ac-dash-card ac-vat-card ac-vat-card--net {{ !$netPositive ? 'ac-dash-card--unbalanced' : '' }}">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">صافي الضريبة المستحقة</span>
            <div class="ac-dash-card__icon {{ $netPositive ? 'ac-dash-card__icon--green' : 'ac-dash-card__icon--red' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 14L15 8"/>
                    <circle cx="9.5" cy="8.5" r="1.5"/>
                    <circle cx="14.5" cy="13.5" r="1.5"/>
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $netPositive ? 'ac-text-success' : 'ac-text-danger' }}">
            {{ $fmt(abs($netVat)) }}
        </div>
        <div class="ac-dash-card__footer">
            @if($netPositive)
                <span class="ac-badge ac-badge--posted">مستحق للسداد</span>
            @elseif($netVat < 0)
                <span class="ac-badge ac-badge--draft">قابل للاسترداد</span>
            @else
                <span class="ac-badge ac-badge--pending">متساوية</span>
            @endif
        </div>
    </div>
</div>

{{-- ── VAT breakdown bar ────────────────────────────────────────────────── --}}
@if($outputVat > 0 || $inputVat > 0)
@php $barMax = max($outputVat, $inputVat, 1); @endphp
<div class="ac-card ac-vat-bar-card" style="margin-bottom:28px">
    <div class="ac-card__body">
        <div class="ac-vat-bar-wrap">
            <div class="ac-vat-bar-row">
                <span class="ac-vat-bar-label">ضريبة المخرجات</span>
                <div class="ac-vat-bar-track">
                    <div class="ac-vat-bar-fill ac-vat-bar-fill--output"
                         style="width:{{ round(($outputVat / $barMax) * 100, 1) }}%"></div>
                </div>
                <span class="ac-vat-bar-val">{{ $fmt($outputVat) }}</span>
            </div>
            <div class="ac-vat-bar-row">
                <span class="ac-vat-bar-label">ضريبة المدخلات</span>
                <div class="ac-vat-bar-track">
                    <div class="ac-vat-bar-fill ac-vat-bar-fill--input"
                         style="width:{{ round(($inputVat / $barMax) * 100, 1) }}%"></div>
                </div>
                <span class="ac-vat-bar-val">{{ $fmt($inputVat) }}</span>
            </div>
            <div class="ac-vat-bar-row ac-vat-bar-row--net">
                <span class="ac-vat-bar-label">صافي الضريبة</span>
                <div class="ac-vat-bar-track">
                    <div class="ac-vat-bar-fill {{ $netPositive ? 'ac-vat-bar-fill--net-pos' : 'ac-vat-bar-fill--net-neg' }}"
                         style="width:{{ round((abs($netVat) / $barMax) * 100, 1) }}%"></div>
                </div>
                <span class="ac-vat-bar-val {{ $netPositive ? 'ac-text-success' : 'ac-text-danger' }}">
                    {{ $netPositive ? '' : '(' }}{{ $fmt(abs($netVat)) }}{{ $netPositive ? '' : ')' }}
                </span>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── Sales Invoices table ──────────────────────────────────────────────── --}}
<div class="ac-vat-section-header">
    <h3 class="ac-vat-section-title">
        <span class="ac-vat-section-dot ac-vat-section-dot--output"></span>
        فواتير المبيعات — ضريبة المخرجات
    </h3>
    <span class="ac-vat-section-total">الضريبة: {{ $fmt($outputVat) }}</span>
</div>

@if($salesInvoices->isEmpty())
    <div class="ac-empty" style="margin-bottom:28px">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14,2 14,8 20,8"/>
        </svg>
        <div>
            <p style="font-weight:700;margin:0">لا توجد فواتير مبيعات بضريبة في هذه الفترة</p>
        </div>
    </div>
@else
    <div class="ac-card" style="margin-bottom:28px">
        <div style="overflow-x:auto">
            <table class="ac-table ac-vat-table">
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>التاريخ</th>
                        <th>العميل</th>
                        <th>الحالة</th>
                        <th class="ac-num-col">المبلغ قبل الضريبة</th>
                        <th class="ac-num-col">نسبة الضريبة</th>
                        <th class="ac-num-col ac-vat-col--output">مبلغ الضريبة</th>
                        <th class="ac-num-col">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesInvoices as $inv)
                        <tr>
                            <td>
                                <a href="{{ route('accounting.invoices.show', $inv->id) }}"
                                   class="ac-link">{{ $inv->invoice_number }}</a>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($inv->issue_date)->format('Y-m-d') }}</td>
                            <td>{{ $inv->party_name }}</td>
                            <td>
                                <span class="ac-badge ac-badge--{{ $statusMod($inv->status) }}">
                                    {{ $statusLabel($inv->status) }}
                                </span>
                            </td>
                            <td class="ac-num-col">{{ $fmt($inv->subtotal) }}</td>
                            <td class="ac-num-col">{{ $inv->tax_rate }}%</td>
                            <td class="ac-num-col ac-vat-col--output">
                                <strong>{{ $fmt($inv->tax_amount) }}</strong>
                            </td>
                            <td class="ac-num-col">{{ $fmt($inv->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="ac-vat-totals-row">
                        <td colspan="4" class="ac-vat-totals-label">الإجمالي ({{ $salesInvoices->count() }} فاتورة)</td>
                        <td class="ac-num-col">{{ $fmt($salesInvoices->sum('subtotal')) }}</td>
                        <td class="ac-num-col">—</td>
                        <td class="ac-num-col ac-vat-col--output">
                            <strong>{{ $fmt($outputVat) }}</strong>
                        </td>
                        <td class="ac-num-col">{{ $fmt($salesInvoices->sum('amount')) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endif

{{-- ── Purchase Invoices table ───────────────────────────────────────────── --}}
<div class="ac-vat-section-header">
    <h3 class="ac-vat-section-title">
        <span class="ac-vat-section-dot ac-vat-section-dot--input"></span>
        فواتير المشتريات — ضريبة المدخلات
    </h3>
    <span class="ac-vat-section-total">الضريبة: {{ $fmt($inputVat) }}</span>
</div>

@if($purchaseInvoices->isEmpty())
    <div class="ac-empty" style="margin-bottom:28px">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14,2 14,8 20,8"/>
        </svg>
        <div>
            <p style="font-weight:700;margin:0">لا توجد فواتير مشتريات بضريبة في هذه الفترة</p>
        </div>
    </div>
@else
    <div class="ac-card" style="margin-bottom:28px">
        <div style="overflow-x:auto">
            <table class="ac-table ac-vat-table">
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>التاريخ</th>
                        <th>المورد</th>
                        <th>الحالة</th>
                        <th class="ac-num-col">المبلغ قبل الضريبة</th>
                        <th class="ac-num-col">نسبة الضريبة</th>
                        <th class="ac-num-col ac-vat-col--input">مبلغ الضريبة</th>
                        <th class="ac-num-col">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseInvoices as $inv)
                        <tr>
                            <td>
                                <a href="{{ route('accounting.purchase-invoices.show', $inv->id) }}"
                                   class="ac-link">{{ $inv->invoice_number }}</a>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($inv->issue_date)->format('Y-m-d') }}</td>
                            <td>{{ $inv->party_name }}</td>
                            <td>
                                <span class="ac-badge ac-badge--{{ $statusMod($inv->status) }}">
                                    {{ $statusLabel($inv->status) }}
                                </span>
                            </td>
                            <td class="ac-num-col">{{ $fmt($inv->subtotal) }}</td>
                            <td class="ac-num-col">{{ $inv->tax_rate }}%</td>
                            <td class="ac-num-col ac-vat-col--input">
                                <strong>{{ $fmt($inv->tax_amount) }}</strong>
                            </td>
                            <td class="ac-num-col">{{ $fmt($inv->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="ac-vat-totals-row">
                        <td colspan="4" class="ac-vat-totals-label">الإجمالي ({{ $purchaseInvoices->count() }} فاتورة)</td>
                        <td class="ac-num-col">{{ $fmt($purchaseInvoices->sum('subtotal')) }}</td>
                        <td class="ac-num-col">—</td>
                        <td class="ac-num-col ac-vat-col--input">
                            <strong>{{ $fmt($inputVat) }}</strong>
                        </td>
                        <td class="ac-num-col">{{ $fmt($purchaseInvoices->sum('amount')) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endif

{{-- ── Net VAT summary box ───────────────────────────────────────────────── --}}
<div class="ac-vat-net-box {{ $netPositive ? 'ac-vat-net-box--payable' : 'ac-vat-net-box--refund' }}">
    <div class="ac-vat-net-box__label">
        @if($netPositive)
            صافي الضريبة المستحقة للسداد للجهات الضريبية
        @else
            صافي الضريبة القابلة للاسترداد من الجهات الضريبية
        @endif
    </div>
    <div class="ac-vat-net-box__calc">
        <span>ضريبة المخرجات: <strong>{{ $fmt($outputVat) }}</strong></span>
        <span class="ac-vat-net-box__minus">−</span>
        <span>ضريبة المدخلات: <strong>{{ $fmt($inputVat) }}</strong></span>
        <span class="ac-vat-net-box__eq">=</span>
        <span class="ac-vat-net-box__result {{ $netPositive ? 'ac-text-success' : 'ac-text-danger' }}">
            {{ $fmt(abs($netVat)) }}
            @if(!$netPositive) <small>(استرداد)</small> @endif
        </span>
    </div>
</div>

@endsection

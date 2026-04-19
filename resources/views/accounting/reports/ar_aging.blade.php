@extends('accounting._layout')

@section('title', 'تقرير تقادم الذمم المدينة')

@php
$fmt     = fn(float $n) => number_format($n, 2);
$asOfHuman = \Carbon\Carbon::parse($as_of)->locale('ar')->translatedFormat('j F Y');
$overdueRatio = $total_outstanding > 0
    ? round(($total_overdue / $total_outstanding) * 100, 1)
    : 0;
@endphp

@section('topbar-actions')
    <div class="ac-topbar-actions">
        <a href="{{ route('accounting.reports.ar-aging', array_filter(['as_of' => $as_of, 'export' => 'pdf'])) }}"
           class="ac-btn ac-btn--primary ac-btn--sm" target="_blank">
            PDF
        </a>
        <a href="{{ route('accounting.invoices.index') }}"
           class="ac-btn ac-btn--secondary ac-btn--sm">الفواتير</a>
        <a href="{{ route('accounting.customers.index') }}"
           class="ac-btn ac-btn--secondary ac-btn--sm">العملاء</a>
    </div>
@endsection

@section('content')

{{-- ── Hero ─────────────────────────────────────────────────────────────── --}}
<div class="ac-aging-hero">
    <div class="ac-aging-hero__content">
        <span class="ac-aging-hero__eyebrow">الذمم المدينة</span>
        <h2 class="ac-aging-hero__title">تقرير التقادم — AR Aging</h2>
        <p class="ac-aging-hero__text">
            يُظهر إجمالي المبالغ المستحقة لكل عميل موزعةً على شرائح زمنية بحسب عمر التأخر.
        </p>
    </div>
    <div class="ac-aging-hero__meta">
        <span class="ac-aging-hero__label">بتاريخ</span>
        <strong class="ac-aging-hero__date">{{ $asOfHuman }}</strong>
    </div>
</div>

{{-- ── Filter ───────────────────────────────────────────────────────────── --}}
<div class="ac-card ac-report-filter-card">
    <div class="ac-card__body">
        <form method="GET" action="{{ route('accounting.reports.ar-aging') }}" class="ac-report-filter">
            <div class="ac-form-row">
                <div class="ac-form-group">
                    <label class="ac-label" for="as_of">بتاريخ</label>
                    <input id="as_of" name="as_of" type="date" class="ac-control"
                           value="{{ $as_of }}">
                </div>
                <div class="ac-form-group ac-form-group--end">
                    <button type="submit" class="ac-btn ac-btn--primary">تطبيق</button>
                    <a href="{{ route('accounting.reports.ar-aging') }}"
                       class="ac-btn ac-btn--secondary">اليوم</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── Summary cards ────────────────────────────────────────────────────── --}}
<div class="ac-dash-grid ac-dash-grid--4" style="margin-bottom:24px">
    {{-- إجمالي المستحقات --}}
    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي المستحقات</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $fmt($total_outstanding) }}</div>
        <div class="ac-dash-card__footer">{{ $customer_count }} عميل</div>
    </div>

    {{-- إجمالي المتأخرة --}}
    <div class="ac-dash-card {{ $total_overdue > 0 ? 'ac-dash-card--unbalanced' : '' }}">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي المتأخرة</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $total_overdue > 0 ? 'ac-text-danger' : 'ac-text-success' }}">
            {{ $fmt($total_overdue) }}
        </div>
        <div class="ac-dash-card__footer">
            {{ $overdueRatio }}% من الإجمالي
        </div>
    </div>

    {{-- جارية (غير متأخرة) --}}
    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">جارية (لم تتأخر)</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20,6 9,17 4,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-success">{{ $fmt($totals->current) }}</div>
        <div class="ac-dash-card__footer">
            @if($total_outstanding > 0)
                {{ round(($totals->current / $total_outstanding) * 100, 1) }}% من الإجمالي
            @else
                لا توجد مستحقات
            @endif
        </div>
    </div>

    {{-- أطول تأخير --}}
    <div class="ac-dash-card {{ $max_days_overdue > 90 ? 'ac-dash-card--unbalanced' : '' }}">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">أطول تأخير</span>
            <div class="ac-dash-card__icon {{ $max_days_overdue > 60 ? 'ac-dash-card__icon--red' : 'ac-dash-card__icon--blue' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12,6 12,12 16,14"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $max_days_overdue > 60 ? 'ac-text-danger' : '' }}">
            {{ $max_days_overdue > 0 ? $max_days_overdue . ' يوم' : '—' }}
        </div>
        <div class="ac-dash-card__footer">
            @if($max_days_overdue > 90)
                تحتاج إجراء فوري
            @elseif($max_days_overdue > 30)
                تحتاج متابعة
            @elseif($max_days_overdue > 0)
                ضمن المعقول
            @else
                لا تأخيرات
            @endif
        </div>
    </div>
</div>

{{-- ── Aging buckets summary bar ────────────────────────────────────────── --}}
@if($total_outstanding > 0)
<div class="ac-card" style="margin-bottom:20px">
    <div class="ac-card__body">
        <div class="ac-aging-bar-wrap">
            <div class="ac-aging-bar">
                @php
                    $buckets = [
                        ['val' => $totals->current, 'cls' => 'ac-aging-bar__seg--current', 'label' => 'جاري'],
                        ['val' => $totals->b1_30,   'cls' => 'ac-aging-bar__seg--30',     'label' => '1-30 يوم'],
                        ['val' => $totals->b31_60,  'cls' => 'ac-aging-bar__seg--60',     'label' => '31-60 يوم'],
                        ['val' => $totals->b61_90,  'cls' => 'ac-aging-bar__seg--90',     'label' => '61-90 يوم'],
                        ['val' => $totals->b91plus, 'cls' => 'ac-aging-bar__seg--91plus', 'label' => '+90 يوم'],
                    ];
                @endphp
                @foreach($buckets as $seg)
                    @php $pct = round(($seg['val'] / $total_outstanding) * 100, 1) @endphp
                    @if($pct > 0)
                        <div class="ac-aging-bar__seg {{ $seg['cls'] }} ac-progress-fill"
                             data-pct="{{ $pct }}"
                             title="{{ $seg['label'] }}: {{ $fmt($seg['val']) }} ({{ $pct }}%)">
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="ac-aging-legend">
                @foreach($buckets as $seg)
                    <div class="ac-aging-legend__item">
                        <span class="ac-aging-legend__dot {{ $seg['cls'] }}"></span>
                        <span class="ac-aging-legend__label">{{ $seg['label'] }}</span>
                        <span class="ac-aging-legend__val">{{ $fmt($seg['val']) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── Main table ───────────────────────────────────────────────────────── --}}
@if($rows->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
            <rect x="9" y="3" width="6" height="4" rx="1"/>
        </svg>
        <div>
            <p style="font-weight:700;margin:0">لا توجد مستحقات بتاريخ {{ $asOfHuman }}</p>
            <p style="font-size:13px;color:var(--ac-text-muted);margin:4px 0 0">
                جميع الفواتير مدفوعة أو لم تُصدر بعد.
            </p>
        </div>
    </div>
@else
    <div class="ac-card">
        <div style="overflow-x:auto">
            <table class="ac-table ac-aging-table">
                <thead>
                    <tr>
                        <th class="ac-aging-table__col-name">العميل</th>
                        <th class="ac-aging-table__col-bucket ac-aging-th--current">جاري</th>
                        <th class="ac-aging-table__col-bucket ac-aging-th--30">1 – 30 يوم</th>
                        <th class="ac-aging-table__col-bucket ac-aging-th--60">31 – 60 يوم</th>
                        <th class="ac-aging-table__col-bucket ac-aging-th--90">61 – 90 يوم</th>
                        <th class="ac-aging-table__col-bucket ac-aging-th--91plus">+90 يوم</th>
                        <th class="ac-aging-table__col-total">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr class="ac-aging-row">
                            <td class="ac-aging-row__name">
                                <a href="{{ route('accounting.customers.show', $row->customer_id) }}"
                                   class="ac-aging-customer-link">
                                    {{ $row->customer_name }}
                                </a>
                                <span class="ac-aging-inv-count">{{ $row->invoice_count }} فاتورة</span>
                            </td>
                            <td class="ac-aging-cell ac-aging-cell--current">
                                {{ $row->current > 0 ? $fmt($row->current) : '—' }}
                            </td>
                            <td class="ac-aging-cell ac-aging-cell--30">
                                @if($row->b1_30 > 0)
                                    <span class="ac-aging-amount ac-aging-amount--30">{{ $fmt($row->b1_30) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="ac-aging-cell ac-aging-cell--60">
                                @if($row->b31_60 > 0)
                                    <span class="ac-aging-amount ac-aging-amount--60">{{ $fmt($row->b31_60) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="ac-aging-cell ac-aging-cell--90">
                                @if($row->b61_90 > 0)
                                    <span class="ac-aging-amount ac-aging-amount--90">{{ $fmt($row->b61_90) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="ac-aging-cell ac-aging-cell--91plus">
                                @if($row->b91plus > 0)
                                    <span class="ac-aging-amount ac-aging-amount--91plus">{{ $fmt($row->b91plus) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="ac-aging-cell ac-aging-cell--total">
                                <strong>{{ $fmt($row->total) }}</strong>
                                @if($row->worst_days > 0)
                                    <span class="ac-aging-worst">{{ $row->worst_days }}ي</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="ac-aging-totals-row">
                        <td class="ac-aging-totals-label">الإجمالي</td>
                        <td class="ac-aging-cell ac-aging-cell--current">
                            <strong>{{ $fmt($totals->current) }}</strong>
                        </td>
                        <td class="ac-aging-cell ac-aging-cell--30">
                            <strong class="{{ $totals->b1_30 > 0 ? 'ac-text-warning' : '' }}">
                                {{ $fmt($totals->b1_30) }}
                            </strong>
                        </td>
                        <td class="ac-aging-cell ac-aging-cell--60">
                            <strong class="{{ $totals->b31_60 > 0 ? 'ac-text-danger' : '' }}">
                                {{ $fmt($totals->b31_60) }}
                            </strong>
                        </td>
                        <td class="ac-aging-cell ac-aging-cell--90">
                            <strong class="{{ $totals->b61_90 > 0 ? 'ac-text-danger' : '' }}">
                                {{ $fmt($totals->b61_90) }}
                            </strong>
                        </td>
                        <td class="ac-aging-cell ac-aging-cell--91plus">
                            <strong class="{{ $totals->b91plus > 0 ? 'ac-text-danger' : '' }}">
                                {{ $fmt($totals->b91plus) }}
                            </strong>
                        </td>
                        <td class="ac-aging-cell ac-aging-cell--total">
                            <strong>{{ $fmt($totals->total) }}</strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endif

@endsection

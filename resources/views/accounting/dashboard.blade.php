@extends('accounting._layout')

@section('title', 'لوحة التحكم')

@php
$arabicMonths = [
    '01'=>'يناير','02'=>'فبراير','03'=>'مارس','04'=>'أبريل',
    '05'=>'مايو', '06'=>'يونيو','07'=>'يوليو','08'=>'أغسطس',
    '09'=>'سبتمبر','10'=>'أكتوبر','11'=>'نوفمبر','12'=>'ديسمبر',
];
$fmt = fn(float $n) => number_format($n, 2);
$periodLabel = function(string $p) use ($arabicMonths): string {
    [$yr, $mo] = explode('-', $p);
    return ($arabicMonths[$mo] ?? $mo) . ' ' . $yr;
};
@endphp

@section('topbar-actions')
    <a href="{{ route('accounting.transactions.create') }}" class="ac-btn ac-btn--primary ac-btn--sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        معاملة جديدة
    </a>
@endsection

@section('content')

{{-- ══════════════════════════════════════════════════════
     SECTION 1 — الأموال المتاحة
     ══════════════════════════════════════════════════════ --}}
<p class="ac-dash-label">الأموال المتاحة</p>
<div class="ac-dash-grid ac-dash-grid--3">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">الخزنة</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $fmt($cashBalance) }}</div>
        <div class="ac-dash-card__footer">رصيد الخزنة الحالي</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">البنك</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $fmt($bankBalance) }}</div>
        <div class="ac-dash-card__footer">رصيد البنك الحالي</div>
    </div>

    <div class="ac-dash-card ac-dash-card--total">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي النقدية</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $fmt($totalCash) }}</div>
        <div class="ac-dash-card__footer">الخزنة + البنك</div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════
     SECTION 1b — الذمم المدينة والدائنة
     ══════════════════════════════════════════════════════ --}}
<p class="ac-dash-label">الذمم المستحقة</p>
<div class="ac-dash-grid ac-dash-grid--2">

    <div class="ac-dash-card ac-dash-card--ar">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">الذمم المدينة</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $fmt($arOutstanding) }}</div>
        <div class="ac-dash-card__footer">
            مستحق من العملاء —
            <a href="{{ route('accounting.reports.ar-aging') }}" class="ac-link">تقرير التقادم</a>
        </div>
    </div>

    <div class="ac-dash-card ac-dash-card--ap">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">الذمم الدائنة</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    <polyline points="9,22 9,12 15,12 15,22"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $fmt($apOutstanding) }}</div>
        <div class="ac-dash-card__footer">
            مستحق للموردين —
            <a href="{{ route('accounting.reports.ap-aging') }}" class="ac-link">تقرير التقادم</a>
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════
     SECTION 2 — الإيرادات والمصروفات
     ══════════════════════════════════════════════════════ --}}
<p class="ac-dash-label">الإيرادات والمصروفات</p>
<div class="ac-period-grid">

    {{-- Revenue panel --}}
    <div class="ac-period-card">
        <div class="ac-period-card__head ac-period-card__head--green">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"/>
                <polyline points="17,6 23,6 23,12"/>
            </svg>
            <span>الإيرادات</span>
        </div>
        <div class="ac-period-card__body">
            <div class="ac-period-row">
                <span class="ac-period-row__label">اليوم</span>
                <span class="ac-period-row__value ac-period-row__value--green">{{ $fmt($revenueToday) }}</span>
            </div>
            <div class="ac-period-row">
                <span class="ac-period-row__label">آخر 7 أيام</span>
                <span class="ac-period-row__value">{{ $fmt($revenueWeek) }}</span>
            </div>
            <div class="ac-period-row ac-period-row--bold">
                <span class="ac-period-row__label">هذا الشهر</span>
                <span class="ac-period-row__value ac-period-row__value--green">{{ $fmt($revenueMonth) }}</span>
            </div>
        </div>
    </div>

    {{-- Expense panel --}}
    <div class="ac-period-card">
        <div class="ac-period-card__head ac-period-card__head--red">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="23,18 13.5,8.5 8.5,13.5 1,6"/>
                <polyline points="17,18 23,18 23,12"/>
            </svg>
            <span>المصروفات</span>
        </div>
        <div class="ac-period-card__body">
            <div class="ac-period-row">
                <span class="ac-period-row__label">اليوم</span>
                <span class="ac-period-row__value ac-period-row__value--red">{{ $fmt($expenseToday) }}</span>
            </div>
            <div class="ac-period-row">
                <span class="ac-period-row__label">آخر 7 أيام</span>
                <span class="ac-period-row__value">{{ $fmt($expenseWeek) }}</span>
            </div>
            <div class="ac-period-row ac-period-row--bold">
                <span class="ac-period-row__label">هذا الشهر</span>
                <span class="ac-period-row__value ac-period-row__value--red">{{ $fmt($expenseMonth) }}</span>
            </div>
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════
     SECTION 3 — صافي الربح
     ══════════════════════════════════════════════════════ --}}
<p class="ac-dash-label">صافي الربح</p>
<div class="ac-dash-grid ac-dash-grid--2">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">صافي الشهر</span>
            <div class="ac-dash-card__icon {{ $profitMonth >= 0 ? 'ac-dash-card__icon--green' : 'ac-dash-card__icon--red' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    @if($profitMonth >= 0)
                        <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"/>
                        <polyline points="17,6 23,6 23,12"/>
                    @else
                        <polyline points="23,18 13.5,8.5 8.5,13.5 1,6"/>
                        <polyline points="17,18 23,18 23,12"/>
                    @endif
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $profitMonth < 0 ? 'ac-text-danger' : 'ac-text-success' }}">
            {{ $fmt($profitMonth) }}
        </div>
        <div class="ac-dash-card__footer">
            @if($profitMonth >= 0)
                <span class="ac-dash-card__trend--up">▲ ربح هذا الشهر</span>
            @else
                <span class="ac-dash-card__trend--down">▼ خسارة هذا الشهر</span>
            @endif
        </div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">صافي 7 أيام</span>
            <div class="ac-dash-card__icon {{ $profitWeek >= 0 ? 'ac-dash-card__icon--green' : 'ac-dash-card__icon--red' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $profitWeek < 0 ? 'ac-text-danger' : 'ac-text-success' }}">
            {{ $fmt($profitWeek) }}
        </div>
        <div class="ac-dash-card__footer">
            @if($profitWeek >= 0)
                <span class="ac-dash-card__trend--up">▲ آخر 7 أيام</span>
            @else
                <span class="ac-dash-card__trend--down">▼ آخر 7 أيام</span>
            @endif
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════
     SECTION 4 — الرسوم البيانية
     ══════════════════════════════════════════════════════ --}}
<p class="ac-dash-label">التحليل البياني</p>

<div class="ac-charts-row">

    {{-- ── Chart 1: Bar + Line — إيرادات / مصروفات / صافي ربح ─────────── --}}
    <div class="ac-chart-card">
        <div class="ac-chart-card__title">
            <span>📊</span> الإيرادات والمصروفات — آخر 12 شهراً
        </div>
        @if(array_sum($chartData['revenues']) == 0 && array_sum($chartData['expenses']) == 0)
            <div class="ac-chart-empty">
                <div class="ac-chart-empty__icon">📭</div>
                <div>لا توجد بيانات كافية لعرض الرسم البياني</div>
            </div>
        @else
            <div class="ac-chart-wrap">
                <canvas id="chartRevExp"></canvas>
            </div>
        @endif
    </div>

    {{-- ── Chart 2: Doughnut — توزيع المصروفات هذا الشهر ──────────────── --}}
    <div class="ac-chart-card">
        <div class="ac-chart-card__title">
            <span>🍩</span> توزيع المصروفات — هذا الشهر
        </div>
        @if(empty($breakdownData['values']) || array_sum($breakdownData['values']) == 0)
            <div class="ac-chart-empty">
                <div class="ac-chart-empty__icon">📭</div>
                <div>لا توجد مصروفات هذا الشهر</div>
            </div>
        @else
            <div class="ac-chart-wrap--doughnut">
                <canvas id="chartBreakdown"></canvas>
            </div>
            <div class="ac-chart-legend" id="breakdownLegend"></div>
        @endif
    </div>

</div>

{{-- ══════════════════════════════════════════════════════
     SECTION 4b — الرسم الأسبوعي
     ══════════════════════════════════════════════════════ --}}
<div class="ac-chart-card" style="margin-bottom:1.75rem;">

    {{-- Header: title + month filter ──────────────────────────────────── --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.1rem;">
        <div class="ac-chart-card__title" style="margin-bottom:0;">
            <span>📅</span> الإيرادات والمصروفات الأسبوعية
        </div>
        <form method="GET" action="{{ route('accounting.dashboard') }}#chartWeekly"
              style="display:flex;align-items:center;gap:.5rem;">
            <label style="font-size:.8rem;color:var(--ac-muted);white-space:nowrap;">الشهر</label>
            <input type="month"
                   name="week_month"
                   value="{{ $weekMonth }}"
                   max="{{ now()->format('Y-m') }}"
                   class="ac-control"
                   style="width:160px;padding:6px 10px;font-size:.85rem;"
                   onchange="this.form.submit()">
        </form>
    </div>

    {{-- Chart or empty state ────────────────────────────────────────────── --}}
    @if(array_sum($weeklyChartData['revenues']) == 0 && array_sum($weeklyChartData['expenses']) == 0)
        <div class="ac-chart-empty">
            <div class="ac-chart-empty__icon">📭</div>
            <div>لا توجد بيانات لهذا الشهر</div>
        </div>
    @else
        <div class="ac-chart-wrap" style="height:240px;">
            <canvas id="chartWeekly"></canvas>
        </div>
    @endif

</div>

{{-- ══════════════════════════════════════════════════════
     SECTION 5 — آخر المعاملات
     ══════════════════════════════════════════════════════ --}}
<div class="ac-page-header">
    <p class="ac-dash-label" style="margin:0;">آخر المعاملات</p>
    <a href="{{ route('accounting.transactions.create') }}" class="ac-btn ac-btn--primary ac-btn--sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        معاملة جديدة
    </a>
</div>

<div class="ac-card">
    <div class="ac-card__body" style="padding:0;">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>النوع</th>
                    <th>الوصف</th>
                    <th>من</th>
                    <th>إلى</th>
                    <th class="ac-table__num">المبلغ</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTransactions as $tx)
                <tr>
                    <td>
                        <span class="ac-txn-badge ac-txn-badge--{{ $tx->type }}">
                            {{ \App\Modules\Accounting\Models\Transaction::typeLabel($tx->type) }}
                        </span>
                    </td>
                    <td class="ac-table__muted">{{ $tx->description ?: '—' }}</td>
                    <td><span class="ac-account-chip">{{ $tx->fromAccount->name ?? '—' }}</span></td>
                    <td><span class="ac-account-chip">{{ $tx->toAccount->name ?? '—' }}</span></td>
                    <td class="ac-table__num ac-table__amount">{{ number_format($tx->amount, 2) }}</td>
                    <td class="ac-table__muted">{{ $tx->transaction_date->format('Y-m-d') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="ac-table__empty">لا توجد معاملات بعد</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($recentTransactions->isNotEmpty())
<div class="ac-dash-more">
    <a href="{{ route('accounting.transactions.index') }}" class="ac-btn ac-btn--secondary ac-btn--sm">
        عرض كل المعاملات ←
    </a>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    /* ── Shared defaults ─────────────────────────────────────────────────── */
    Chart.defaults.font.family = "'Cairo', sans-serif";
    Chart.defaults.font.size   = 12;
    Chart.defaults.color       = '#64748b';

    const fmt = (n) => new Intl.NumberFormat('ar-SA', {
        minimumFractionDigits: 0, maximumFractionDigits: 2
    }).format(n);

    /* ── Data from PHP ───────────────────────────────────────────────────── */
    const chartData       = @json($chartData);
    const breakdownData   = @json($breakdownData);
    const weeklyChartData = @json($weeklyChartData);

    /* ══════════════════════════════════════════════════════════════════════
       Chart 1 — Bar + Line: إيرادات / مصروفات / صافي الربح
       ══════════════════════════════════════════════════════════════════════ */
    const ctxRevExp = document.getElementById('chartRevExp');
    if (ctxRevExp) {
        new Chart(ctxRevExp, {
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'الإيرادات',
                        data: chartData.revenues,
                        backgroundColor: 'rgba(21, 128, 61, 0.75)',
                        borderRadius: 4,
                        borderSkipped: false,
                        order: 2,
                    },
                    {
                        type: 'bar',
                        label: 'المصروفات',
                        data: chartData.expenses,
                        backgroundColor: 'rgba(185, 28, 28, 0.7)',
                        borderRadius: 4,
                        borderSkipped: false,
                        order: 2,
                    },
                    {
                        type: 'line',
                        label: 'صافي الربح',
                        data: chartData.profit,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.08)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#2563eb',
                        order: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { boxWidth: 12, padding: 16 },
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` ${ctx.dataset.label}: ${fmt(ctx.parsed.y)}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 45 },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,.05)' },
                        ticks: { callback: (v) => fmt(v) },
                    },
                },
            },
        });
    }

    /* ══════════════════════════════════════════════════════════════════════
       Chart 2 — Doughnut: توزيع المصروفات
       ══════════════════════════════════════════════════════════════════════ */
    const DOUGHNUT_COLORS = [
        '#ef4444','#f97316','#eab308','#22c55e','#3b82f6','#a855f7',
    ];

    const ctxBreakdown = document.getElementById('chartBreakdown');
    if (ctxBreakdown && breakdownData.values.length > 0) {
        const total = breakdownData.values.reduce((a, b) => a + b, 0);

        new Chart(ctxBreakdown, {
            type: 'doughnut',
            data: {
                labels: breakdownData.labels,
                datasets: [{
                    data: breakdownData.values,
                    backgroundColor: DOUGHNUT_COLORS.slice(0, breakdownData.values.length),
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const pct = ((ctx.parsed / total) * 100).toFixed(1);
                                return ` ${ctx.label}: ${fmt(ctx.parsed)} (${pct}%)`;
                            },
                        },
                    },
                },
            },
        });

        /* Build custom legend */
        const legend = document.getElementById('breakdownLegend');
        if (legend) {
            breakdownData.labels.forEach((label, i) => {
                const pct = ((breakdownData.values[i] / total) * 100).toFixed(1);
                legend.insertAdjacentHTML('beforeend', `
                    <div class="ac-chart-legend-item">
                        <span class="ac-chart-legend-dot"
                              style="background:${DOUGHNUT_COLORS[i] ?? '#ccc'}"></span>
                        <span class="ac-chart-legend-name">${label}</span>
                        <span class="ac-chart-legend-val">${pct}%</span>
                    </div>
                `);
            });
        }
    }

    /* ══════════════════════════════════════════════════════════════════════
       Chart 3 — Weekly Grouped Bars: إيرادات / مصروفات أسبوعية
       ══════════════════════════════════════════════════════════════════════ */
    const ctxWeekly = document.getElementById('chartWeekly');
    if (ctxWeekly) {
        new Chart(ctxWeekly, {
            type: 'bar',
            data: {
                labels: weeklyChartData.labels,
                datasets: [
                    {
                        label: 'الإيرادات',
                        data: weeklyChartData.revenues,
                        backgroundColor: 'rgba(21, 128, 61, 0.78)',
                        borderRadius: 4,
                        borderSkipped: false,
                    },
                    {
                        label: 'المصروفات',
                        data: weeklyChartData.expenses,
                        backgroundColor: 'rgba(185, 28, 28, 0.72)',
                        borderRadius: 4,
                        borderSkipped: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { boxWidth: 12, padding: 14 },
                    },
                    tooltip: {
                        callbacks: {
                            title: (items) => 'أسبوع ' + items[0].label,
                            label: (ctx) => ` ${ctx.dataset.label}: ${fmt(ctx.parsed.y)}`,
                            afterBody: (items) => {
                                const rev = items.find(i => i.datasetIndex === 0)?.parsed.y ?? 0;
                                const exp = items.find(i => i.datasetIndex === 1)?.parsed.y ?? 0;
                                const net = rev - exp;
                                const sign = net >= 0 ? '▲ ربح' : '▼ خسارة';
                                return [`──────────`, ` ${sign}: ${fmt(Math.abs(net))}`];
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 40 },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,.05)' },
                        ticks: { callback: (v) => fmt(v) },
                    },
                },
            },
        });
    }

})();
</script>
@endpush

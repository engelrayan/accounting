@extends('accounting._layout')

@section('title', 'قائمة الأرباح والخسائر')

@php
$fmt = fn(float $n) => number_format(abs($n), 2);
$baseQuery = request()->except(['export']);
@endphp

@section('topbar-actions')
    <a href="{{ route('accounting.reports.trial-balance') }}"
       class="ac-btn ac-btn--secondary ac-btn--sm">ميزان المراجعة ←</a>
@endsection

@section('content')

<div class="ac-report-hero ac-report-hero--profit">
    <div class="ac-report-hero__content">
        <span class="ac-report-hero__eyebrow">قائمة مالية</span>
        <h2 class="ac-report-hero__title">الأرباح والخسائر</h2>
        <p class="ac-report-hero__text">
            عرض تفصيلي للإيرادات والمصروفات خلال الفترة المحددة، مع احتساب صافي الربح أو الخسارة.
        </p>
    </div>
    <div class="ac-report-hero__meta">
        <span class="ac-report-hero__badge">{{ $label }}</span>
        <strong>{{ $human_range }}</strong>
        <div class="ac-topbar-actions">
            <a href="{{ route('accounting.reports.income-expense', $baseQuery) }}"
               class="ac-btn ac-btn--secondary ac-btn--sm">
                الدخل والمصروف
            </a>
            <a href="{{ route('accounting.reports.profit-loss', array_merge($baseQuery, ['export' => 'excel'])) }}"
               class="ac-btn ac-btn--secondary ac-btn--sm">Excel</a>
            <a href="{{ route('accounting.reports.profit-loss', array_merge($baseQuery, ['export' => 'pdf'])) }}"
               class="ac-btn ac-btn--primary ac-btn--sm">PDF</a>
        </div>
    </div>
</div>

@include('accounting.reports._period_presets', [
    'routeName' => 'accounting.reports.profit-loss',
    'currentPeriod' => $period,
    'extraQuery' => [],
])

{{-- ══ Date filter ══════════════════════════════════════════════════════════ --}}
<div class="ac-card ac-report-filter-card">
    <div class="ac-card__body">
        <form method="GET" action="{{ route('accounting.reports.profit-loss') }}"
              class="ac-report-filter">
            <input type="hidden" name="period" value="custom">
            <div class="ac-form-row">
                <div class="ac-form-group">
                    <label class="ac-label" for="from">من تاريخ</label>
                    <input id="from" name="from" type="date" class="ac-control"
                           value="{{ $from ?? '' }}">
                </div>
                <div class="ac-form-group">
                    <label class="ac-label" for="to">إلى تاريخ</label>
                    <input id="to" name="to" type="date" class="ac-control"
                           value="{{ $to ?? '' }}">
                </div>
                <div class="ac-form-group ac-form-group--end">
                    <button type="submit" class="ac-btn ac-btn--primary">تطبيق</button>
                    @if($from || $to)
                        <a href="{{ route('accounting.reports.profit-loss') }}"
                           class="ac-btn ac-btn--secondary">مسح</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ══ Smart Insights ═══════════════════════════════════════════════════════ --}}
@if(count($insights) > 0)
<div class="ac-insights-list">
    @foreach($insights as $ins)
    <div class="ac-insight ac-insight--{{ $ins['level'] }}">

        {{-- Icon --}}
        <div class="ac-insight__icon">
            @if($ins['level'] === 'error')
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            @elseif($ins['level'] === 'warning')
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            @elseif($ins['level'] === 'success')
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                    <polyline points="22,4 12,14.01 9,11.01"/>
                </svg>
            @else
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            @endif
        </div>

        {{-- Body --}}
        <div class="ac-insight__body">
            <span class="ac-insight__message">{{ $ins['message'] }}</span>
            @if(!empty($ins['suggestion']))
                <span class="ac-insight__suggestion">{{ $ins['suggestion'] }}</span>
            @endif
        </div>

        {{-- CTA --}}
        @if(!empty($ins['cta']))
            <a href="{{ route($ins['cta']['route'], $ins['cta']['params']) }}"
               class="ac-insight__cta">
                {{ $ins['cta']['label'] }}
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="15,18 9,12 15,6"/>
                </svg>
            </a>
        @endif

    </div>
    @endforeach
</div>
@endif

{{-- ══ Summary Cards ════════════════════════════════════════════════════════ --}}
<div class="ac-dash-grid ac-dash-grid--4">

    {{-- Total Revenue --}}
    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي الإيرادات</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="19" x2="12" y2="5"/>
                    <polyline points="5,12 12,5 19,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $total_revenue > 0 ? 'ac-text-success' : 'ac-text-muted' }}">
            {{ $fmt($total_revenue) }}
        </div>
        <div class="ac-dash-card__footer">
            @if($has_comparison && $revenue_change_pct !== null)
                <span class="ac-pl-change {{ $revenue_change_pct >= 0 ? 'ac-pl-change--up' : 'ac-pl-change--down' }}">
                    {{ $revenue_change_pct >= 0 ? '▲' : '▼' }} {{ abs($revenue_change_pct) }}% عن الفترة السابقة
                </span>
            @else
                <span class="ac-dash-card__trend--neutral">مجموع طرف الإيرادات</span>
            @endif
        </div>
    </div>

    {{-- Total Expenses --}}
    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي المصروفات</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <polyline points="19,12 12,19 5,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-danger">{{ $fmt($total_expenses) }}</div>
        <div class="ac-dash-card__footer">
            @if($has_comparison && $expense_change_pct !== null)
                {{-- expense going up = bad (down), going down = good (up) --}}
                <span class="ac-pl-change {{ $expense_change_pct <= 0 ? 'ac-pl-change--up' : 'ac-pl-change--down' }}">
                    {{ $expense_change_pct >= 0 ? '▲' : '▼' }} {{ abs($expense_change_pct) }}% عن الفترة السابقة
                </span>
            @else
                <span class="ac-dash-card__trend--neutral">مجموع طرف المصروفات</span>
            @endif
        </div>
    </div>

    {{-- Net Profit / Loss --}}
    <div class="ac-dash-card {{ $net_profit < 0 ? 'ac-dash-card--unbalanced' : '' }}">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">{{ $net_profit >= 0 ? 'صافي الربح' : 'صافي الخسارة' }}</span>
            <div class="ac-dash-card__icon {{ $net_profit >= 0 ? 'ac-dash-card__icon--green' : 'ac-dash-card__icon--red' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    @if($net_profit >= 0)
                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                        <polyline points="22,4 12,14.01 9,11.01"/>
                    @else
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    @endif
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $net_profit >= 0 ? 'ac-text-success' : 'ac-text-danger' }}">
            {{ $fmt($net_profit) }}
        </div>
        <div class="ac-dash-card__footer">
            @if($has_comparison && $profit_change_pct !== null)
                <span class="ac-pl-change {{ $profit_change_pct >= 0 ? 'ac-pl-change--up' : 'ac-pl-change--down' }}">
                    {{ $profit_change_pct >= 0 ? '▲' : '▼' }} {{ abs($profit_change_pct) }}% عن الفترة السابقة
                </span>
            @else
                <span class="ac-dash-card__trend--neutral">{{ $net_profit >= 0 ? 'الشركة رابحة' : 'الشركة خاسرة' }}</span>
            @endif
        </div>
    </div>

    {{-- Profit Margin --}}
    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">هامش الربح</span>
            <div class="ac-dash-card__icon {{ $margin_pct >= 20 ? 'ac-dash-card__icon--green' : ($margin_pct > 0 ? 'ac-dash-card__icon--amber' : 'ac-dash-card__icon--red') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6"  y1="20" x2="6"  y2="14"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $margin_pct >= 20 ? 'ac-text-success' : ($margin_pct > 0 ? '' : 'ac-text-danger') }}">
            {{ $margin_pct }}%
        </div>
        <div class="ac-dash-card__footer">
            @if($margin_pct >= 30)
                <span class="ac-dash-card__trend--up">ممتاز ✓</span>
            @elseif($margin_pct >= 20)
                <span class="ac-dash-card__trend--up">جيد</span>
            @elseif($margin_pct > 0)
                <span class="ac-dash-card__trend--neutral">منخفض</span>
            @else
                <span class="ac-dash-card__trend--down">لا يوجد ربح</span>
            @endif
        </div>
    </div>

</div>

{{-- ══ Revenue Section ══════════════════════════════════════════════════════ --}}
<div class="ac-pl-section">
    <div class="ac-pl-section__header ac-pl-section__header--revenue">
        <div class="ac-pl-section__title">
            <span class="ac-pl-section__dot ac-pl-section__dot--revenue"></span>
            الإيرادات
            <span class="ac-pl-section__count">{{ $revenue_rows->count() }} حساب</span>
        </div>
        <div class="ac-pl-section__total ac-text-success">{{ $fmt($total_revenue) }}</div>
    </div>

    @if($revenue_rows->isEmpty())
        {{-- ── Revenue empty state with CTA ── --}}
        <div class="ac-pl-empty">
            <div class="ac-pl-empty__icon ac-pl-empty__icon--revenue">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <line x1="12" y1="19" x2="12" y2="5"/>
                    <polyline points="5,12 12,5 19,12"/>
                    <line x1="4" y1="22" x2="20" y2="22"/>
                </svg>
            </div>
            <div class="ac-pl-empty__body">
                <p class="ac-pl-empty__title">لا توجد إيرادات في هذه الفترة</p>
                <p class="ac-pl-empty__sub">لم يتم تسجيل أي قيود إيرادات مُرحَّلة. ابدأ بتسجيل إيراداتك لتتضح لك الصورة المالية الكاملة.</p>
            </div>
            <a href="{{ route('accounting.transactions.create') }}" class="ac-btn ac-btn--primary ac-btn--sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                إضافة إيراد
            </a>
        </div>
    @else
        <div class="ac-pl-col-head">
            <span class="ac-pl-col-head__name">الحساب</span>
            <span class="ac-pl-col-head__bar">النسبة من الإجمالي</span>
            <span class="ac-pl-col-head__pct">%</span>
            <span class="ac-pl-col-head__amount">المبلغ</span>
        </div>
        <div class="ac-pl-rows">
            @foreach($revenue_rows as $row)
            <div class="ac-pl-row">
                <div class="ac-pl-row__name">
                    <code class="ac-code-tag">{{ $row->code }}</code>
                    <span>{{ $row->name }}</span>
                </div>
                <div class="ac-pl-row__bar-wrap">
                    <div class="ac-pl-row__bar ac-pl-row__bar--revenue ac-progress-fill"
                         data-pct="{{ $row->pct }}"></div>
                </div>
                <div class="ac-pl-row__pct ac-text-muted">{{ $row->pct }}%</div>
                <div class="ac-pl-row__amount ac-text-success">{{ $fmt($row->amount) }}</div>
            </div>
            @endforeach
            <div class="ac-pl-rows__total">
                <span>إجمالي الإيرادات</span>
                <span class="ac-text-success ac-font-bold">{{ $fmt($total_revenue) }}</span>
            </div>
        </div>
    @endif
</div>

{{-- ══ Expense Section ══════════════════════════════════════════════════════ --}}
<div class="ac-pl-section">
    <div class="ac-pl-section__header ac-pl-section__header--expense">
        <div class="ac-pl-section__title">
            <span class="ac-pl-section__dot ac-pl-section__dot--expense"></span>
            المصروفات
            <span class="ac-pl-section__count">{{ $expense_rows->count() }} حساب</span>
        </div>
        <div class="ac-pl-section__total ac-text-danger">{{ $fmt($total_expenses) }}</div>
    </div>

    @if($expense_rows->isEmpty())
        {{-- ── Expense empty state ── --}}
        <div class="ac-pl-empty">
            <div class="ac-pl-empty__icon ac-pl-empty__icon--expense">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <polyline points="19,12 12,19 5,12"/>
                    <line x1="4" y1="2" x2="20" y2="2"/>
                </svg>
            </div>
            <div class="ac-pl-empty__body">
                <p class="ac-pl-empty__title">لا توجد مصروفات في هذه الفترة</p>
                <p class="ac-pl-empty__sub">لم يتم تسجيل أي مصروفات مُرحَّلة في هذه الفترة الزمنية.</p>
            </div>
        </div>
    @else
        <div class="ac-pl-col-head">
            <span class="ac-pl-col-head__name">الحساب</span>
            <span class="ac-pl-col-head__bar">النسبة من المصروفات</span>
            <span class="ac-pl-col-head__pct">%</span>
            <span class="ac-pl-col-head__amount">المبلغ</span>
        </div>
        <div class="ac-pl-rows">
            @foreach($expense_rows as $row)
            <div class="ac-pl-row">
                <div class="ac-pl-row__name">
                    <code class="ac-code-tag">{{ $row->code }}</code>
                    <div class="ac-pl-row__name-stack">
                        <span>{{ $row->name }}</span>
                        @if($row->pct_of_activity > 0)
                            <span class="ac-pl-row__activity-label">
                                يمثل {{ $row->pct_of_activity }}% من إجمالي النشاط
                            </span>
                        @endif
                    </div>
                </div>
                <div class="ac-pl-row__bar-wrap">
                    <div class="ac-pl-row__bar ac-pl-row__bar--expense ac-progress-fill"
                         data-pct="{{ $row->pct }}"></div>
                </div>
                <div class="ac-pl-row__pct ac-text-muted">{{ $row->pct }}%</div>
                <div class="ac-pl-row__amount ac-text-danger">{{ $fmt($row->amount) }}</div>
            </div>
            @endforeach
            <div class="ac-pl-rows__total">
                <span>إجمالي المصروفات</span>
                <span class="ac-text-danger ac-font-bold">{{ $fmt($total_expenses) }}</span>
            </div>
        </div>
    @endif
</div>

{{-- ══ Net Result Banner ════════════════════════════════════════════════════ --}}
<div class="ac-pl-net {{ $net_profit >= 0 ? 'ac-pl-net--profit' : 'ac-pl-net--loss' }}">
    <div class="ac-pl-net__left">
        <div class="ac-pl-net__label">
            @if($net_profit >= 0)
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                    <polyline points="22,4 12,14.01 9,11.01"/>
                </svg>
                صافي الربح
            @else
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                صافي الخسارة
            @endif
        </div>
        <div class="ac-pl-net__reason">{{ $net_reason }}</div>
    </div>
    <div class="ac-pl-net__right">
        <span class="ac-pl-net__amount">{{ $fmt($net_profit) }}</span>
        <span class="ac-pl-net__margin">هامش {{ $margin_pct }}%</span>
    </div>
</div>

{{-- ══ Period Comparison ════════════════════════════════════════════════════ --}}
@if($has_comparison)
<div class="ac-pl-compare-card">
    <div class="ac-pl-compare-card__title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/>
        </svg>
        المقارنة مع الفترة السابقة
        <span class="ac-pl-compare-card__period">{{ $prev_from }} — {{ $prev_to }}</span>
    </div>
    <div class="ac-pl-compare-grid">

        {{-- Revenue comparison --}}
        <div class="ac-pl-compare-item">
            <div class="ac-pl-compare-item__label">إيرادات الفترة السابقة</div>
            <div class="ac-pl-compare-item__value">{{ $fmt($prev_revenue) }}</div>
            <div class="ac-pl-compare-item__current">الفترة الحالية: <strong>{{ $fmt($total_revenue) }}</strong></div>
            @if($revenue_change_pct !== null)
                <div class="ac-pl-change ac-pl-change--lg {{ $revenue_change_pct >= 0 ? 'ac-pl-change--up' : 'ac-pl-change--down' }}">
                    {{ $revenue_change_pct >= 0 ? '▲' : '▼' }} {{ abs($revenue_change_pct) }}%
                </div>
            @else
                <div class="ac-pl-change ac-pl-change--neutral">لا توجد بيانات سابقة</div>
            @endif
        </div>

        {{-- Expense comparison --}}
        <div class="ac-pl-compare-item">
            <div class="ac-pl-compare-item__label">مصروفات الفترة السابقة</div>
            <div class="ac-pl-compare-item__value">{{ $fmt($prev_expenses) }}</div>
            <div class="ac-pl-compare-item__current">الفترة الحالية: <strong>{{ $fmt($total_expenses) }}</strong></div>
            @if($expense_change_pct !== null)
                <div class="ac-pl-change ac-pl-change--lg {{ $expense_change_pct <= 0 ? 'ac-pl-change--up' : 'ac-pl-change--down' }}">
                    {{ $expense_change_pct >= 0 ? '▲' : '▼' }} {{ abs($expense_change_pct) }}%
                </div>
            @else
                <div class="ac-pl-change ac-pl-change--neutral">لا توجد بيانات سابقة</div>
            @endif
        </div>

        {{-- Net profit comparison --}}
        <div class="ac-pl-compare-item">
            <div class="ac-pl-compare-item__label">
                صافي {{ $prev_net_profit >= 0 ? 'الربح' : 'الخسارة' }} السابق
            </div>
            <div class="ac-pl-compare-item__value {{ $prev_net_profit >= 0 ? 'ac-text-success' : 'ac-text-danger' }}">
                {{ $fmt($prev_net_profit) }}
            </div>
            <div class="ac-pl-compare-item__current">
                الفترة الحالية:
                <strong class="{{ $net_profit >= 0 ? 'ac-text-success' : 'ac-text-danger' }}">
                    {{ $fmt($net_profit) }}
                </strong>
            </div>
            @if($profit_change_pct !== null)
                <div class="ac-pl-change ac-pl-change--lg {{ $profit_change_pct >= 0 ? 'ac-pl-change--up' : 'ac-pl-change--down' }}">
                    {{ $profit_change_pct >= 0 ? '▲' : '▼' }} {{ abs($profit_change_pct) }}%
                </div>
            @else
                <div class="ac-pl-change ac-pl-change--neutral">أول فترة مسجَّلة</div>
            @endif
        </div>

    </div>
</div>
@endif

@endsection

@extends('accounting._layout')

@section('title', 'تقرير الدخل والمصروف')

@php
$fmt = fn(float $n) => number_format(abs($n), 2);
$baseQuery = request()->except(['export']);
$maxIncome = max(1, (float) $series->max('income'));
$maxExpense = max(1, (float) $series->max('expense'));
$seriesModeLabel = match ($series_mode) {
    'daily' => 'يومي',
    'weekly' => 'أسبوعي',
    default => 'شهري',
};
@endphp

@section('topbar-actions')
    <div class="ac-topbar-actions">
        <a href="{{ route('accounting.reports.profit-loss', $baseQuery) }}"
           class="ac-btn ac-btn--secondary ac-btn--sm">الأرباح والخسائر</a>
        <a href="{{ route('accounting.reports.income-expense', array_merge($baseQuery, ['export' => 'excel'])) }}"
           class="ac-btn ac-btn--secondary ac-btn--sm">Excel</a>
        <a href="{{ route('accounting.reports.income-expense', array_merge($baseQuery, ['export' => 'pdf'])) }}"
           class="ac-btn ac-btn--primary ac-btn--sm">PDF</a>
    </div>
@endsection

@section('content')

<div class="ac-report-hero ac-report-hero--income-expense">
    <div class="ac-report-hero__content">
        <span class="ac-report-hero__eyebrow">حركة النشاط</span>
        <h2 class="ac-report-hero__title">صورة سريعة للدخل والمصروف</h2>
        <p class="ac-report-hero__text">
            عرض عملي يوضح أين يأتي الدخل، وأين تذهب المصروفات، وكيف تغير صافي النشاط خلال الفترة المحددة.
        </p>
    </div>
    <div class="ac-report-hero__meta">
        <span class="ac-report-hero__badge">{{ $label }}</span>
        <strong>{{ $human_range }}</strong>
        <span>التجميع: {{ $seriesModeLabel }}</span>
    </div>
</div>

@include('accounting.reports._period_presets', [
    'routeName' => 'accounting.reports.income-expense',
    'currentPeriod' => $period,
    'extraQuery' => [],
])

<div class="ac-card ac-report-filter-card">
    <div class="ac-card__body">
        <form method="GET" action="{{ route('accounting.reports.income-expense') }}" class="ac-report-filter">
            <input type="hidden" name="period" value="custom">
            <div class="ac-form-row">
                <div class="ac-form-group">
                    <label class="ac-label" for="from">من تاريخ</label>
                    <input id="from" name="from" type="date" class="ac-control" value="{{ $from ?? '' }}">
                </div>
                <div class="ac-form-group">
                    <label class="ac-label" for="to">إلى تاريخ</label>
                    <input id="to" name="to" type="date" class="ac-control" value="{{ $to ?? '' }}">
                </div>
                <div class="ac-form-group ac-form-group--end">
                    <button type="submit" class="ac-btn ac-btn--primary">تطبيق</button>
                    <a href="{{ route('accounting.reports.income-expense') }}"
                       class="ac-btn ac-btn--secondary">إعادة الضبط</a>
                </div>
            </div>
        </form>
    </div>
</div>

@if(count($insights) > 0)
    <div class="ac-insights-list">
        @foreach($insights as $ins)
            <div class="ac-insight ac-insight--{{ $ins['level'] }}">
                <div class="ac-insight__body">
                    <span class="ac-insight__message">{{ $ins['message'] }}</span>
                    @if(!empty($ins['suggestion']))
                        <span class="ac-insight__suggestion">{{ $ins['suggestion'] }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="ac-dash-grid ac-dash-grid--4">
    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي الدخل</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="19" x2="12" y2="5"/>
                    <polyline points="5,12 12,5 19,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-success">{{ $fmt($total_income) }}</div>
        <div class="ac-dash-card__footer">متوسط يومي {{ $fmt($average_income) }}</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي المصروف</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <polyline points="19,12 12,19 5,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-danger">{{ $fmt($total_expenses) }}</div>
        <div class="ac-dash-card__footer">متوسط يومي {{ $fmt($average_expense) }}</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">{{ $net_result >= 0 ? 'صافي النشاط' : 'العجز التشغيلي' }}</span>
            <div class="ac-dash-card__icon {{ $net_result >= 0 ? 'ac-dash-card__icon--green' : 'ac-dash-card__icon--red' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19h16"/>
                    <path d="M7 16l3-5 3 2 4-6"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $net_result >= 0 ? 'ac-text-success' : 'ac-text-danger' }}">{{ $fmt($net_result) }}</div>
        <div class="ac-dash-card__footer">متوسط يومي {{ $fmt($average_net) }}</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">أفضل إشارة</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22,7 13.5,15.5 8.5,10.5 2,17"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-report-signal-card__amount">
            {{ $top_income_bucket?->label ?? '—' }}
        </div>
        <div class="ac-dash-card__footer">
            دخل {{ $fmt((float) ($top_income_bucket->income ?? 0)) }}
            @if($top_expense_bucket)
                <span class="ac-report-signal-card__divider">•</span>
                مصروف {{ $fmt((float) $top_expense_bucket->expense) }}
            @endif
        </div>
    </div>
</div>

<div class="ac-report-signal-grid">
    <div class="ac-report-panel">
        <div class="ac-report-panel__header">
            <div>
                <h3>الإيقاع خلال الفترة</h3>
                <p>كل صف يمثل {{ $seriesModeLabel === 'يومي' ? 'يومًا' : ($seriesModeLabel === 'أسبوعي' ? 'أسبوعًا' : 'شهرًا') }} داخل الفترة.</p>
            </div>
            <span class="ac-report-panel__badge">{{ $series->count() }} نقطة</span>
        </div>
        <div class="ac-report-timeline">
            @foreach($series as $bucket)
                <div class="ac-report-timeline__row">
                    <div class="ac-report-timeline__period">
                        <strong>{{ $bucket->label }}</strong>
                        <span>{{ $bucket->range }}</span>
                    </div>
                    <div class="ac-report-timeline__bars">
                        <div class="ac-report-timeline__bar-wrap">
                            <div class="ac-report-timeline__bar ac-report-timeline__bar--income ac-progress-fill"
                                 data-pct="{{ round(($bucket->income / $maxIncome) * 100, 1) }}"></div>
                        </div>
                        <div class="ac-report-timeline__bar-wrap">
                            <div class="ac-report-timeline__bar ac-report-timeline__bar--expense ac-progress-fill"
                                 data-pct="{{ round(($bucket->expense / $maxExpense) * 100, 1) }}"></div>
                        </div>
                    </div>
                    <div class="ac-report-timeline__values">
                        <span class="ac-text-success">دخل {{ $fmt($bucket->income) }}</span>
                        <span class="ac-text-danger">مصروف {{ $fmt($bucket->expense) }}</span>
                        <strong class="{{ $bucket->net >= 0 ? 'ac-text-success' : 'ac-text-danger' }}">
                            صافي {{ $fmt($bucket->net) }}
                        </strong>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="ac-pl-section">
    <div class="ac-pl-section__header ac-pl-section__header--revenue">
        <div class="ac-pl-section__title">
            <span class="ac-pl-section__dot ac-pl-section__dot--revenue"></span>
            أعلى بنود الدخل
            <span class="ac-pl-section__count">{{ $income_rows->count() }} حساب</span>
        </div>
        <div class="ac-pl-section__total ac-text-success">{{ $fmt($total_income) }}</div>
    </div>

    @if($income_rows->isEmpty())
        <div class="ac-pl-empty">
            <div class="ac-pl-empty__body">
                <p class="ac-pl-empty__title">لا توجد بنود دخل مرصودة</p>
                <p class="ac-pl-empty__sub">الفترة الحالية لا تحتوي على إيرادات مُرحلة.</p>
            </div>
        </div>
    @else
        <div class="ac-pl-col-head">
            <span class="ac-pl-col-head__name">الحساب</span>
            <span class="ac-pl-col-head__bar">الحصة من الدخل</span>
            <span class="ac-pl-col-head__pct">%</span>
            <span class="ac-pl-col-head__amount">المبلغ</span>
        </div>
        <div class="ac-pl-rows">
            @foreach($income_rows as $row)
                <div class="ac-pl-row">
                    <div class="ac-pl-row__name">
                        <code class="ac-code-tag">{{ $row->code }}</code>
                        <span>{{ $row->name }}</span>
                    </div>
                    <div class="ac-pl-row__bar-wrap">
                        <div class="ac-pl-row__bar ac-pl-row__bar--revenue ac-progress-fill" data-pct="{{ $row->pct }}"></div>
                    </div>
                    <div class="ac-pl-row__pct ac-text-muted">{{ $row->pct }}%</div>
                    <div class="ac-pl-row__amount ac-text-success">{{ $fmt($row->amount) }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<div class="ac-pl-section">
    <div class="ac-pl-section__header ac-pl-section__header--expense">
        <div class="ac-pl-section__title">
            <span class="ac-pl-section__dot ac-pl-section__dot--expense"></span>
            أعلى بنود المصروف
            <span class="ac-pl-section__count">{{ $expense_rows->count() }} حساب</span>
        </div>
        <div class="ac-pl-section__total ac-text-danger">{{ $fmt($total_expenses) }}</div>
    </div>

    @if($expense_rows->isEmpty())
        <div class="ac-pl-empty">
            <div class="ac-pl-empty__body">
                <p class="ac-pl-empty__title">لا توجد بنود مصروف مرصودة</p>
                <p class="ac-pl-empty__sub">الفترة الحالية لا تحتوي على مصروفات مُرحلة.</p>
            </div>
        </div>
    @else
        <div class="ac-pl-col-head">
            <span class="ac-pl-col-head__name">الحساب</span>
            <span class="ac-pl-col-head__bar">الحصة من المصروف</span>
            <span class="ac-pl-col-head__pct">%</span>
            <span class="ac-pl-col-head__amount">المبلغ</span>
        </div>
        <div class="ac-pl-rows">
            @foreach($expense_rows as $row)
                <div class="ac-pl-row">
                    <div class="ac-pl-row__name">
                        <code class="ac-code-tag">{{ $row->code }}</code>
                        <span>{{ $row->name }}</span>
                    </div>
                    <div class="ac-pl-row__bar-wrap">
                        <div class="ac-pl-row__bar ac-pl-row__bar--expense ac-progress-fill" data-pct="{{ $row->pct }}"></div>
                    </div>
                    <div class="ac-pl-row__pct ac-text-muted">{{ $row->pct }}%</div>
                    <div class="ac-pl-row__amount ac-text-danger">{{ $fmt($row->amount) }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection

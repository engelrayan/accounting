@extends('accounting._layout')

@section('title', 'ميزان المراجعة')

@php
$typeLabels = [
    'asset'     => 'أصول',
    'liability' => 'التزامات',
    'equity'    => 'حقوق ملكية',
    'revenue'   => 'إيرادات',
    'expense'   => 'مصروفات',
];
$typeColors = [
    'asset'     => 'blue',
    'liability' => 'red',
    'equity'    => 'amber',
    'revenue'   => 'green',
    'expense'   => 'purple',
];
$fmt = fn(float $n) => number_format(abs($n), 2);
$baseQuery = request()->except(['export']);
@endphp

@section('topbar-actions')
    <a href="{{ route('accounting.reports.profit-loss') }}" class="ac-btn ac-btn--secondary ac-btn--sm">
        الأرباح والخسائر
    </a>
@endsection

@section('content')

<div class="ac-report-hero ac-report-hero--trial">
    <div class="ac-report-hero__content">
        <span class="ac-report-hero__eyebrow">فحص التوازن</span>
        <h2 class="ac-report-hero__title">ميزان المراجعة</h2>
        <p class="ac-report-hero__text">
            تحقق من سلامة القيود المحاسبية وتوازن أرصدة الحسابات، مع إمكانية الفلترة حسب الفترة ونوع الحساب وتصدير التقرير مباشرةً.
        </p>
    </div>
    <div class="ac-report-hero__meta">
        <span class="ac-report-hero__badge">{{ $label }}</span>
        <strong>{{ $human_range }}</strong>
        <div class="ac-topbar-actions">
            <a href="{{ route('accounting.reports.income-expense', $baseQuery) }}" class="ac-btn ac-btn--secondary ac-btn--sm">
                الدخل والمصروف
            </a>
            <a href="{{ route('accounting.reports.trial-balance', array_merge($baseQuery, ['export' => 'excel'])) }}"
               class="ac-btn ac-btn--secondary ac-btn--sm">Excel</a>
            <a href="{{ route('accounting.reports.trial-balance', array_merge($baseQuery, ['export' => 'pdf'])) }}"
               class="ac-btn ac-btn--primary ac-btn--sm">PDF</a>
        </div>
    </div>
</div>

@include('accounting.reports._period_presets', [
    'routeName' => 'accounting.reports.trial-balance',
    'currentPeriod' => $period,
    'extraQuery' => array_filter(['filter_type' => $filter_type]),
])

{{-- ══ Filters ══════════════════════════════════════════════════════════════ --}}
<div class="ac-card ac-report-filter-card">
    <div class="ac-card__body">
        <form method="GET" action="{{ route('accounting.reports.trial-balance') }}"
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

                <div class="ac-form-group">
                    <label class="ac-label" for="filter_type">نوع الحساب</label>
                    <select id="filter_type" name="filter_type" class="ac-select">
                        <option value="">— الكل —</option>
                        @foreach($typeLabels as $val => $lbl)
                            <option value="{{ $val }}" {{ ($filter_type ?? '') === $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ac-form-group ac-form-group--end">
                    <button type="submit" class="ac-btn ac-btn--primary">تطبيق</button>
                    @if($from || $to || $filter_type)
                        <a href="{{ route('accounting.reports.trial-balance') }}"
                           class="ac-btn ac-btn--secondary">مسح</a>
                    @endif
                </div>

            </div>
        </form>
    </div>
</div>

{{-- ══ Nothing found ════════════════════════════════════════════════════════ --}}
@if($groups->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14,2 14,8 20,8"/>
        </svg>
        <p>لا توجد قيود مُرحَّلة في الفترة المحددة.</p>
    </div>
@else

{{-- ══ Smart Insights ════════════════════════════════════════════════════════ --}}
@if(count($insights) > 0)
    <div class="ac-insights-list">
        @foreach($insights as $ins)
        <div class="ac-insight ac-insight--{{ $ins['level'] }}">
            @if($ins['level'] === 'error')
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            @elseif($ins['level'] === 'warning')
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            @else
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/>
                </svg>
            @endif
            <span>{{ $ins['message'] }}</span>
        </div>
        @endforeach
    </div>
@endif

{{-- ══ Summary Cards ══════════════════════════════════════════════════════════ --}}
<div class="ac-dash-grid ac-dash-grid--4">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي المدين</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><polyline points="19,12 12,19 5,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($total_debit, 2) }}</div>
        <div class="ac-dash-card__footer">مجموع طرف المدين</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي الدائن</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="19" x2="12" y2="5"/><polyline points="5,12 12,5 19,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($total_credit, 2) }}</div>
        <div class="ac-dash-card__footer">مجموع طرف الدائن</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">الفرق</span>
            <div class="ac-dash-card__icon {{ $is_balanced ? 'ac-dash-card__icon--green' : 'ac-dash-card__icon--red' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ !$is_balanced ? 'ac-text-danger' : '' }}">
            {{ number_format($difference, 2) }}
        </div>
        <div class="ac-dash-card__footer">
            @if($is_balanced)
                <span class="ac-dash-card__trend--up">✓ متوازن</span>
            @else
                <span class="ac-dash-card__trend--down">✗ غير متوازن</span>
            @endif
        </div>
    </div>

    <div class="ac-dash-card {{ !$is_balanced ? 'ac-dash-card--unbalanced' : '' }}">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">الحالة</span>
            <div class="ac-dash-card__icon {{ $is_balanced ? 'ac-dash-card__icon--green' : 'ac-dash-card__icon--red' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    @if($is_balanced)
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
        <div class="ac-dash-card__amount {{ $is_balanced ? 'ac-text-success' : 'ac-text-danger' }}">
            {{ $is_balanced ? 'متوازن' : 'خطأ' }}
        </div>
        <div class="ac-dash-card__footer">
            {{ $is_balanced ? 'الميزان سليم' : 'النظام غير متوازن' }}
        </div>
    </div>

</div>

{{-- ══ Grouped Accounts ═══════════════════════════════════════════════════════ --}}
<div class="ac-tb-groups">
    @foreach($groups as $type => $group)
    <div class="ac-tb-group" data-tb-group="{{ $type }}">

        {{-- Group header --}}
        <div class="ac-tb-group__header ac-tb-group__header--{{ $group['color'] }}">
            <div class="ac-tb-group__header-left">
                <button class="ac-tb-collapse-btn" title="طي / توسيع">
                    <svg class="ac-chevron" width="15" height="15" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="6,9 12,15 18,9"/>
                    </svg>
                </button>
                <span class="ac-tb-group__title">{{ $group['label'] }}</span>
                <span class="ac-tb-group__count">{{ $group['accounts']->count() }} حساب</span>
            </div>
            <div class="ac-tb-group__totals">
                <span class="ac-tb-group__net">{{ number_format($group['net'], 2) }}</span>
            </div>
        </div>

        {{-- Account rows --}}
        <div class="ac-tb-rows">
            {{-- Column headers --}}
            <div class="ac-tb-col-head">
                <span class="ac-tb-col-head__name">الحساب</span>
                <span class="ac-tb-col-head__balance">الرصيد</span>
                <span class="ac-tb-col-head__indicator">الطرف</span>
                <span class="ac-tb-col-head__action"></span>
            </div>

            @foreach($group['accounts'] as $row)
            <div class="ac-tb-row {{ $row->is_abnormal ? 'ac-tb-row--abnormal' : '' }}
                                  {{ !$row->is_active  ? 'ac-tb-row--inactive'  : '' }}">

                <div class="ac-tb-row__name">
                    <code class="ac-code-tag">{{ $row->code }}</code>
                    {{ $row->name }}
                    @if(!$row->is_active)
                        <span class="ac-badge ac-badge--off">موقوف</span>
                    @endif
                    @if($row->is_abnormal)
                        <span class="ac-badge ac-badge--warn" title="الرصيد في الاتجاه الخاطئ">رصيد عكسي</span>
                    @endif
                </div>

                <div class="ac-tb-row__balance {{ $row->net_balance < 0 ? 'ac-text-danger' : '' }}">
                    {{ $fmt($row->net_balance) }}
                </div>

                <div class="ac-tb-row__indicator">
                    <span class="ac-dr-cr-badge ac-dr-cr-badge--{{ $row->normal_balance }}">
                        {{ $row->normal_balance === 'debit' ? 'مدين' : 'دائن' }}
                    </span>
                </div>

                <div class="ac-tb-row__action">
                    <a href="{{ route('accounting.reports.account-ledger', $row->account_id) }}{{ ($from || $to) ? '?from='.($from??'').'&to='.($to??'') : '' }}"
                       class="ac-icon-btn" title="عرض حركات الحساب">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </a>
                </div>

            </div>
            @endforeach

            {{-- Group subtotal --}}
            <div class="ac-tb-subtotal">
                <span class="ac-tb-subtotal__label">مجموع {{ $group['label'] }}</span>
                <span class="ac-tb-subtotal__value">{{ number_format($group['net'], 2) }}</span>
            </div>
        </div>

    </div>
    @endforeach
</div>

{{-- ══ Grand Total row ════════════════════════════════════════════════════════ --}}
<div class="ac-tb-grand-total {{ !$is_balanced ? 'ac-tb-grand-total--error' : '' }}">
    <span class="ac-tb-grand-total__label">
        @if($is_balanced)
            الميزان متوازن ✓
        @else
            النظام غير متوازن ✗ — الفرق {{ number_format($difference, 2) }}
        @endif
    </span>
    <div class="ac-tb-grand-total__figures">
        <span>مدين: <strong>{{ number_format($total_debit, 2) }}</strong></span>
        <span>دائن: <strong>{{ number_format($total_credit, 2) }}</strong></span>
    </div>
</div>

@endif
@endsection

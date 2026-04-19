@extends('accounting._layout')

@section('title', 'الميزانية العمومية')

@php
$fmt      = fn(float $n) => number_format(abs($n), 2);
$baseQuery = request()->only(['as_of']);
@endphp

@section('topbar-actions')
    <a href="{{ route('accounting.reports.profit-loss') }}" class="ac-btn ac-btn--secondary ac-btn--sm">
        الأرباح والخسائر
    </a>
    <a href="{{ route('accounting.reports.trial-balance') }}" class="ac-btn ac-btn--secondary ac-btn--sm">
        ميزان المراجعة
    </a>
@endsection

@section('content')

{{-- ════════════════════════════════════════════════════════════════════════
     Hero
     ═══════════════════════════════════════════════════════════════════════ --}}
<div class="ac-report-hero ac-report-hero--bs">
    <div class="ac-report-hero__content">
        <span class="ac-report-hero__eyebrow">قائمة مالية</span>
        <h2 class="ac-report-hero__title">الميزانية العمومية</h2>
        <p class="ac-report-hero__text">
            لقطة فورية لأصول الشركة والتزاماتها وحقوق ملكيتها في تاريخ محدد،
            مع التحقق من توازن المعادلة المحاسبية: الأصول = الالتزامات + حقوق الملكية.
        </p>
    </div>
    <div class="ac-report-hero__meta">
        <span class="ac-report-hero__badge">
            حتى {{ \Carbon\Carbon::parse($as_of)->translatedFormat('j F Y') }}
        </span>
        <div class="ac-topbar-actions">
            <a href="{{ route('accounting.reports.balance-sheet', array_merge($baseQuery, ['export' => 'excel'])) }}"
               class="ac-btn ac-btn--secondary ac-btn--sm">Excel</a>
            <a href="{{ route('accounting.reports.balance-sheet', array_merge($baseQuery, ['export' => 'pdf'])) }}"
               class="ac-btn ac-btn--primary ac-btn--sm">PDF</a>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════════════
     As-of date filter
     ═══════════════════════════════════════════════════════════════════════ --}}
<div class="ac-card ac-report-filter-card">
    <div class="ac-card__body">
        <form method="GET" action="{{ route('accounting.reports.balance-sheet') }}"
              class="ac-report-filter">
            <div class="ac-form-row">
                <div class="ac-form-group">
                    <label class="ac-label" for="as_of">الميزانية حتى تاريخ</label>
                    <input id="as_of" name="as_of" type="date" class="ac-control"
                           value="{{ $as_of }}">
                </div>
            </div>
            <div class="ac-report-filter__actions">
                <button type="submit" class="ac-btn ac-btn--primary ac-btn--sm">تطبيق</button>
                <a href="{{ route('accounting.reports.balance-sheet') }}"
                   class="ac-btn ac-btn--ghost ac-btn--sm">اليوم</a>
            </div>
        </form>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════════════
     Insights
     ═══════════════════════════════════════════════════════════════════════ --}}
@if($insights)
<ul class="ac-insights-list">
    @foreach($insights as $insight)
    <li class="ac-insight ac-insight--{{ $insight['level'] }}">
        <div class="ac-insight__body">
            <span class="ac-insight__msg">{{ $insight['message'] }}</span>
            @if(!empty($insight['suggestion']))
                <span class="ac-insight__hint">{{ $insight['suggestion'] }}</span>
            @endif
        </div>
    </li>
    @endforeach
</ul>
@endif

{{-- ════════════════════════════════════════════════════════════════════════
     Summary cards
     ═══════════════════════════════════════════════════════════════════════ --}}
<div class="ac-dash-grid ac-dash-grid--4 ac-mt-2">

    {{-- Total Assets --}}
    <div class="ac-dash-card">
        <div class="ac-dash-card__label">إجمالي الأصول</div>
        <div class="ac-dash-card__icon ac-dash-card__icon--blue">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="7" width="20" height="14" rx="2"/>
                <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
            </svg>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($total_assets, 2) }}</div>
        <div class="ac-dash-card__footer">
            <span class="ac-text-muted">{{ $assets->count() }} حساب</span>
        </div>
    </div>

    {{-- Total Liabilities --}}
    <div class="ac-dash-card">
        <div class="ac-dash-card__label">إجمالي الالتزامات</div>
        <div class="ac-dash-card__icon ac-dash-card__icon--red">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($total_liabilities, 2) }}</div>
        <div class="ac-dash-card__footer">
            <span class="ac-text-muted">{{ $liabilities->count() }} حساب</span>
        </div>
    </div>

    {{-- Total Equity --}}
    <div class="ac-dash-card">
        <div class="ac-dash-card__label">حقوق الملكية</div>
        <div class="ac-dash-card__icon ac-dash-card__icon--amber">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v6l4 2"/>
            </svg>
        </div>
        <div class="ac-dash-card__amount {{ $total_equity < 0 ? 'ac-text-danger' : '' }}">
            {{ $total_equity < 0 ? '(' . $fmt($total_equity) . ')' : number_format($total_equity, 2) }}
        </div>
        <div class="ac-dash-card__footer">
            <span class="ac-text-muted">{{ $equity->count() }} حساب</span>
        </div>
    </div>

    {{-- Balance status --}}
    <div class="ac-dash-card {{ $is_balanced ? 'ac-dash-card--success' : 'ac-dash-card--alert' }}">
        <div class="ac-dash-card__label">حالة الميزانية</div>
        <div class="ac-dash-card__icon {{ $is_balanced ? 'ac-dash-card__icon--green' : 'ac-dash-card__icon--red' }}">
            @if($is_balanced)
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            @else
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            @endif
        </div>
        <div class="ac-dash-card__amount {{ $is_balanced ? 'ac-text-success' : 'ac-text-danger' }}" style="font-size:18px;">
            {{ $is_balanced ? 'متوازنة ✓' : 'غير متوازنة' }}
        </div>
        <div class="ac-dash-card__footer">
            @if(! $is_balanced)
                <span class="ac-text-danger">فرق {{ number_format($difference, 2) }}</span>
            @else
                <span class="ac-text-muted">A = L + E</span>
            @endif
        </div>
    </div>

</div>

{{-- ════════════════════════════════════════════════════════════════════════
     No data state
     ═══════════════════════════════════════════════════════════════════════ --}}
@if($assets->isEmpty() && $liabilities->isEmpty() && $equity->isEmpty())
<div class="ac-empty-state">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="ac-empty-state__icon">
        <rect x="2" y="7" width="20" height="14" rx="2"/>
        <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
    </svg>
    <h3 class="ac-empty-state__title">لا توجد بيانات</h3>
    <p class="ac-empty-state__text">لم يتم تسجيل أي قيود محاسبية مُرحَّلة حتى هذا التاريخ.</p>
    <a href="{{ route('accounting.journal-entries.create') }}" class="ac-btn ac-btn--primary ac-btn--sm">
        إضافة قيد محاسبي
    </a>
</div>
@else

{{-- ════════════════════════════════════════════════════════════════════════
     Main Balance Sheet Layout
     ═══════════════════════════════════════════════════════════════════════ --}}
<div class="ac-bs-layout">

    {{-- ── Right Column: Assets ──────────────────────────────────────────── --}}
    <div class="ac-bs-col">

        {{-- Assets Section --}}
        <div class="ac-bs-section ac-bs-section--asset">
            <div class="ac-bs-section__header">
                <div class="ac-bs-section__dot ac-bs-section__dot--blue"></div>
                <h3 class="ac-bs-section__title">الأصول</h3>
                <span class="ac-bs-section__total">{{ number_format($total_assets, 2) }}</span>
            </div>

            @if($assets->isEmpty())
                <p class="ac-bs-empty">لا توجد أصول مُسجَّلة.</p>
            @else
            <div class="ac-bs-rows">
                @foreach($assets as $row)
                <div class="ac-bs-row {{ $row->is_abnormal ? 'ac-bs-row--abnormal' : '' }}">
                    <div class="ac-bs-row__info">
                        <span class="ac-bs-row__code">{{ $row->code }}</span>
                        <span class="ac-bs-row__name">{{ $row->name }}</span>
                        @if($row->is_abnormal)
                            <span class="ac-bs-row__abnormal-tag">رصيد معكوس</span>
                        @endif
                    </div>
                    <div class="ac-bs-row__right">
                        <span class="ac-bs-row__amount {{ $row->is_abnormal ? 'ac-text-danger' : '' }}">
                            {{ $row->is_abnormal ? '(' . $fmt($row->net_balance) . ')' : $fmt($row->net_balance) }}
                        </span>
                        <div class="ac-bs-row__bar">
                            <div class="ac-bs-row__bar-fill ac-bs-row__bar-fill--blue"
                                 style="width: {{ min(100, max(0, $row->pct)) }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="ac-bs-section__subtotal">
                <span>إجمالي الأصول</span>
                <span class="ac-bs-section__subtotal-amt">{{ number_format($total_assets, 2) }}</span>
            </div>
            @endif
        </div>

    </div>{{-- end ac-bs-col assets --}}

    {{-- ── Left Column: Liabilities + Equity ───────────────────────────── --}}
    <div class="ac-bs-col">

        {{-- Liabilities Section --}}
        <div class="ac-bs-section ac-bs-section--liability">
            <div class="ac-bs-section__header">
                <div class="ac-bs-section__dot ac-bs-section__dot--red"></div>
                <h3 class="ac-bs-section__title">الالتزامات</h3>
                <span class="ac-bs-section__total">{{ number_format($total_liabilities, 2) }}</span>
            </div>

            @if($liabilities->isEmpty())
                <p class="ac-bs-empty">لا توجد التزامات مُسجَّلة.</p>
            @else
            <div class="ac-bs-rows">
                @foreach($liabilities as $row)
                <div class="ac-bs-row {{ $row->is_abnormal ? 'ac-bs-row--abnormal' : '' }}">
                    <div class="ac-bs-row__info">
                        <span class="ac-bs-row__code">{{ $row->code }}</span>
                        <span class="ac-bs-row__name">{{ $row->name }}</span>
                        @if($row->is_abnormal)
                            <span class="ac-bs-row__abnormal-tag">رصيد معكوس</span>
                        @endif
                    </div>
                    <div class="ac-bs-row__right">
                        <span class="ac-bs-row__amount {{ $row->is_abnormal ? 'ac-text-danger' : '' }}">
                            {{ $row->is_abnormal ? '(' . $fmt($row->net_balance) . ')' : $fmt($row->net_balance) }}
                        </span>
                        <div class="ac-bs-row__bar">
                            <div class="ac-bs-row__bar-fill ac-bs-row__bar-fill--red"
                                 style="width: {{ min(100, max(0, $row->pct)) }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="ac-bs-section__subtotal">
                <span>إجمالي الالتزامات</span>
                <span class="ac-bs-section__subtotal-amt">{{ number_format($total_liabilities, 2) }}</span>
            </div>
            @endif
        </div>

        {{-- Equity Section --}}
        <div class="ac-bs-section ac-bs-section--equity ac-mt-2">
            <div class="ac-bs-section__header">
                <div class="ac-bs-section__dot ac-bs-section__dot--amber"></div>
                <h3 class="ac-bs-section__title">حقوق الملكية</h3>
                <span class="ac-bs-section__total {{ $total_equity < 0 ? 'ac-text-danger' : '' }}">
                    {{ $total_equity < 0 ? '(' . $fmt($total_equity) . ')' : number_format($total_equity, 2) }}
                </span>
            </div>

            @if($equity->isEmpty())
                <p class="ac-bs-empty">لا توجد حسابات حقوق ملكية مُسجَّلة.</p>
            @else
            <div class="ac-bs-rows">
                @foreach($equity as $row)
                <div class="ac-bs-row {{ $row->is_abnormal ? 'ac-bs-row--abnormal' : '' }}">
                    <div class="ac-bs-row__info">
                        <span class="ac-bs-row__code">{{ $row->code }}</span>
                        <span class="ac-bs-row__name">{{ $row->name }}</span>
                        @if($row->is_abnormal)
                            <span class="ac-bs-row__abnormal-tag">رصيد معكوس</span>
                        @endif
                    </div>
                    <div class="ac-bs-row__right">
                        <span class="ac-bs-row__amount {{ $row->is_abnormal ? 'ac-text-danger' : '' }}">
                            {{ $row->is_abnormal ? '(' . $fmt($row->net_balance) . ')' : $fmt($row->net_balance) }}
                        </span>
                        <div class="ac-bs-row__bar">
                            <div class="ac-bs-row__bar-fill ac-bs-row__bar-fill--amber"
                                 style="width: {{ min(100, max(0, $row->pct)) }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="ac-bs-section__subtotal">
                <span>إجمالي حقوق الملكية</span>
                <span class="ac-bs-section__subtotal-amt {{ $total_equity < 0 ? 'ac-text-danger' : '' }}">
                    {{ $total_equity < 0 ? '(' . $fmt($total_equity) . ')' : number_format($total_equity, 2) }}
                </span>
            </div>
            @endif

            {{-- Liabilities + Equity combined --}}
            <div class="ac-bs-section__subtotal ac-bs-section__subtotal--combined">
                <span>إجمالي الالتزامات + حقوق الملكية</span>
                <span class="ac-bs-section__subtotal-amt">{{ number_format($total_liabilities_and_equity, 2) }}</span>
            </div>
        </div>

    </div>{{-- end ac-bs-col liabilities+equity --}}

</div>{{-- end ac-bs-layout --}}

{{-- ════════════════════════════════════════════════════════════════════════
     Equation Check Footer
     ═══════════════════════════════════════════════════════════════════════ --}}
<div class="ac-bs-equation {{ $is_balanced ? 'ac-bs-equation--balanced' : 'ac-bs-equation--unbalanced' }}">
    <div class="ac-bs-equation__side">
        <div class="ac-bs-equation__label">الأصول</div>
        <div class="ac-bs-equation__value">{{ number_format($total_assets, 2) }}</div>
    </div>
    <div class="ac-bs-equation__op">
        {{ $is_balanced ? '=' : '≠' }}
    </div>
    <div class="ac-bs-equation__side">
        <div class="ac-bs-equation__label">الالتزامات + حقوق الملكية</div>
        <div class="ac-bs-equation__value">{{ number_format($total_liabilities_and_equity, 2) }}</div>
    </div>
    <div class="ac-bs-equation__badge {{ $is_balanced ? 'ac-bs-equation__badge--ok' : 'ac-bs-equation__badge--err' }}">
        @if($is_balanced)
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            متوازنة
        @else
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            فرق {{ number_format($difference, 2) }}
        @endif
    </div>
</div>

@endif {{-- end not empty --}}

@endsection

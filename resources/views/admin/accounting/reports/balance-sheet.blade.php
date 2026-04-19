@extends('accounting._layout')

@section('title', 'الميزانية العمومية')

@php
    $fmt       = fn(float $n) => number_format(abs($n), 2);
    $baseQuery = request()->only(['as_of']);

    /**
     * Render one account row (used for all three sections).
     * Inlined as a PHP closure so we avoid a separate partial file.
     */
    $renderRow = function (object $row, string $amountClass, string $barClass) use ($fmt): string {
        $nodeAttr   = 'data-bs-node="account-' . $row->id . '"';
        $parentAttr = $row->depth > 0
            ? 'data-bs-parent="account-' . $row->parent_id . '"'
            : '';
        $hiddenAttr = '';   // all rows visible by default; JS handles collapse

        $depthClass  = 'ac-bs-row--depth-' . min($row->depth, 4);
        $parentClass = $row->is_parent ? 'ac-bs-row--parent' : '';
        $abnormClass = $row->is_abnormal ? 'ac-bs-row--abnormal' : '';

        $amount = $row->is_abnormal
            ? '(' . $fmt($row->net_balance) . ')'
            : $fmt($row->net_balance);

        $amountColorClass = $row->is_abnormal ? 'ac-text-danger' : $amountClass;

        $toggleBtn = $row->is_parent
            ? '<button type="button" class="ac-bs-toggle" aria-label="طيّ/بسط">
                   <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5">
                       <polyline points="6,9 12,15 18,9"/>
                   </svg>
               </button>'
            : '<span class="ac-bs-toggle-spacer"></span>';

        $ownNote = ($row->is_parent && abs($row->own_balance) > 0.001)
            ? '<span class="ac-bs-row__own-note" title="رصيد الحساب نفسه بدون الأبناء">'
              . '(' . $fmt($row->own_balance) . ')'
              . '</span>'
            : '';

        return <<<HTML
<div class="ac-bs-row {$depthClass} {$parentClass} {$abnormClass}"
     {$nodeAttr} {$parentAttr} {$hiddenAttr}>
    <div class="ac-bs-row__info">
        {$toggleBtn}
        <span class="ac-bs-row__code">{$row->code}</span>
        <div class="ac-bs-row__name-wrap">
            <span class="ac-bs-row__name">{$row->name}</span>
            {$ownNote}
        </div>
        @if(\$row->is_abnormal)
            <span class="ac-bs-row__abnormal-tag">معكوس</span>
        @endif
    </div>
    <div class="ac-bs-row__right">
        <span class="ac-bs-row__amount {$amountColorClass}">{$amount}</span>
        <span class="ac-bs-row__pct ac-text-muted">{$row->pct_of_assets}%</span>
        <div class="ac-bs-row__bar">
            <div class="ac-progress-fill {$barClass}"
                 data-pct="{$row->pct_of_section}"></div>
        </div>
    </div>
</div>
HTML;
    };
@endphp

@section('topbar-actions')
<div style="display:flex;gap:8px;align-items:center;">
    <a href="{{ route('accounting.reports.balance-sheet', array_merge($baseQuery, ['export' => 'excel'])) }}"
       class="ac-btn ac-btn--secondary ac-btn--sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14,2 14,8 20,8"/>
        </svg>
        Excel
    </a>
    <a href="{{ route('accounting.reports.balance-sheet', array_merge($baseQuery, ['export' => 'pdf'])) }}"
       class="ac-btn ac-btn--primary ac-btn--sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14,2 14,8 20,8"/>
            <path d="M9 13h6M9 17h4"/>
        </svg>
        PDF
    </a>
</div>
@endsection

@section('content')

{{-- ════════════════════════════════════════════════════════════
     Hero
     ════════════════════════════════════════════════════════════ --}}
<div class="ac-report-hero ac-report-hero--bs">
    <div class="ac-report-hero__content">
        <span class="ac-report-hero__eyebrow">قائمة مالية</span>
        <h2 class="ac-report-hero__title">الميزانية العمومية</h2>
        <p class="ac-report-hero__text">
            لقطة فورية لأصول الشركة والتزاماتها وحقوق ملكيتها في تاريخ محدد.
            الأصول = الالتزامات + حقوق الملكية.
        </p>
    </div>
    <div class="ac-report-hero__meta">
        <span class="ac-report-hero__badge">
            حتى {{ \Carbon\Carbon::parse($as_of)->translatedFormat('j F Y') }}
        </span>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     Date filter
     ════════════════════════════════════════════════════════════ --}}
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
                <button type="submit" class="ac-btn ac-btn--primary ac-btn--sm">عرض</button>
                <a href="{{ route('accounting.reports.balance-sheet') }}"
                   class="ac-btn ac-btn--ghost ac-btn--sm">اليوم</a>
            </div>
        </form>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════
     Insights
     ════════════════════════════════════════════════════════════ --}}
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

{{-- ════════════════════════════════════════════════════════════
     No data
     ════════════════════════════════════════════════════════════ --}}
@if($assets->isEmpty() && $liabilities->isEmpty() && $equity->isEmpty())
<div class="ac-empty-state">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.5" class="ac-empty-state__icon">
        <rect x="2" y="7" width="20" height="14" rx="2"/>
        <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
    </svg>
    <h3 class="ac-empty-state__title">لا توجد بيانات حتى هذا التاريخ</h3>
    <p class="ac-empty-state__text">سجِّل القيود المحاسبية وارحِّلها لتظهر في الميزانية.</p>
    <a href="{{ route('accounting.journal-entries.create') }}" class="ac-btn ac-btn--primary ac-btn--sm">
        إضافة قيد
    </a>
</div>

@else

{{-- ════════════════════════════════════════════════════════════
     Summary cards
     ════════════════════════════════════════════════════════════ --}}
<div class="ac-dash-grid ac-dash-grid--4 ac-mt-2">

    {{-- Total Assets --}}
    <div class="ac-dash-card">
        <div class="ac-dash-card__label">إجمالي الأصول</div>
        <div class="ac-dash-card__icon ac-dash-card__icon--green">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="7" width="20" height="14" rx="2"/>
                <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                <line x1="12" y1="12" x2="12" y2="16"/>
                <line x1="10" y1="14" x2="14" y2="14"/>
            </svg>
        </div>
        <div class="ac-dash-card__amount ac-text-success">{{ number_format($total_assets, 2) }}</div>
        <div class="ac-dash-card__footer">
            <span class="ac-text-muted">{{ $assets->where('depth', 0)->count() }} مجموعة</span>
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
        <div class="ac-dash-card__amount ac-text-danger">{{ number_format($total_liabilities, 2) }}</div>
        <div class="ac-dash-card__footer">
            @php $leRatio = $total_assets > 0 ? round(($total_liabilities / $total_assets) * 100) : 0 @endphp
            <span class="ac-text-muted">{{ $leRatio }}% من الأصول</span>
        </div>
    </div>

    {{-- Total Equity --}}
    <div class="ac-dash-card">
        <div class="ac-dash-card__label">حقوق الملكية</div>
        <div class="ac-dash-card__icon ac-dash-card__icon--amber">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="8" r="6"/>
                <path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/>
            </svg>
        </div>
        <div class="ac-dash-card__amount {{ $total_equity < 0 ? 'ac-text-danger' : 'ac-text-warning' }}">
            {{ $total_equity < 0 ? '(' . $fmt($total_equity) . ')' : number_format($total_equity, 2) }}
        </div>
        <div class="ac-dash-card__footer">
            @php $eqRatio = $total_assets > 0 ? round(($total_equity / $total_assets) * 100) : 0 @endphp
            <span class="{{ $eqRatio >= 50 ? 'ac-text-success' : 'ac-text-muted' }}">{{ $eqRatio }}% من الأصول</span>
        </div>
    </div>

    {{-- Balance status --}}
    <div class="ac-dash-card {{ $is_balanced ? '' : 'ac-dash-card--alert' }}">
        <div class="ac-dash-card__label">حالة الميزانية</div>
        <div class="ac-dash-card__icon {{ $is_balanced ? 'ac-dash-card__icon--green' : 'ac-dash-card__icon--red' }}">
            @if($is_balanced)
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            @else
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            @endif
        </div>
        <div class="ac-dash-card__amount ac-bs-status-text {{ $is_balanced ? 'ac-text-success' : 'ac-text-danger' }}">
            {{ $is_balanced ? 'متوازنة' : 'غير متوازنة' }}
        </div>
        <div class="ac-dash-card__footer">
            @if($is_balanced)
                <span class="ac-text-muted">A = L + E ✓</span>
            @else
                <span class="ac-text-danger">فرق {{ number_format($difference, 2) }}</span>
            @endif
        </div>
    </div>

</div>

{{-- ════════════════════════════════════════════════════════════
     Column header legend
     ════════════════════════════════════════════════════════════ --}}
<div class="ac-bs-col-legend">
    <span class="ac-bs-col-legend__item">
        <span class="ac-bs-col-legend__dot ac-bs-section__dot--green"></span>
        الأصول
    </span>
    <span class="ac-bs-col-legend__item">
        <span class="ac-bs-col-legend__dot ac-bs-section__dot--red"></span>
        الالتزامات
    </span>
    <span class="ac-bs-col-legend__item">
        <span class="ac-bs-col-legend__dot ac-bs-section__dot--orange"></span>
        حقوق الملكية
    </span>
    <span class="ac-bs-col-legend__sep"></span>
    <span class="ac-bs-col-legend__hint">
        العمود الأخير = نسبة من إجمالي الأصول
    </span>
    <button type="button" class="ac-btn ac-btn--ghost ac-btn--sm" id="bs-expand-all">
        بسط الكل
    </button>
    <button type="button" class="ac-btn ac-btn--ghost ac-btn--sm" id="bs-collapse-all">
        طيّ الكل
    </button>
</div>

{{-- ════════════════════════════════════════════════════════════
     3-Column Balance Sheet
     ════════════════════════════════════════════════════════════ --}}
<div class="ac-bs-grid-3">

    {{-- ── Assets ──────────────────────────────────────────── --}}
    <div class="ac-bs-section ac-bs-section--asset">

        <div class="ac-bs-section__header ac-bs-section__header--green">
            <div class="ac-bs-section__dot ac-bs-section__dot--green"></div>
            <h3 class="ac-bs-section__title">الأصول</h3>
            <span class="ac-bs-section__badge ac-bs-section__badge--green">
                {{ $assets->count() }}
            </span>
        </div>

        @if($assets->isEmpty())
            <p class="ac-bs-empty">لا توجد أصول مُسجَّلة.</p>
        @else
        <div class="ac-bs-col-header">
            <span class="ac-bs-col-header__name">الحساب</span>
            <span class="ac-bs-col-header__right">
                <span>الرصيد</span>
                <span>% الأصول</span>
            </span>
        </div>
        <div class="ac-bs-rows" id="bs-section-assets">
            @foreach($assets as $row)
            <div class="ac-bs-row ac-bs-row--depth-{{ min($row->depth, 4) }} {{ $row->is_parent ? 'ac-bs-row--parent' : '' }} {{ $row->is_abnormal ? 'ac-bs-row--abnormal' : '' }}"
                 data-bs-node="account-{{ $row->id }}"
                 @if($row->depth > 0) data-bs-parent="account-{{ $row->parent_id }}" @endif>

                <div class="ac-bs-row__info">
                    @if($row->is_parent)
                        <button type="button" class="ac-bs-toggle" aria-label="طيّ/بسط">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5">
                                <polyline points="6,9 12,15 18,9"/>
                            </svg>
                        </button>
                    @else
                        <span class="ac-bs-toggle-spacer"></span>
                    @endif
                    <span class="ac-bs-row__code">{{ $row->code }}</span>
                    <div class="ac-bs-row__name-wrap">
                        <span class="ac-bs-row__name">{{ $row->name }}</span>
                        @if($row->is_parent && abs($row->own_balance) > 0.001)
                            <span class="ac-bs-row__own-note">{{ $fmt($row->own_balance) }}</span>
                        @endif
                    </div>
                    @if($row->is_abnormal)
                        <span class="ac-bs-row__abnormal-tag">معكوس</span>
                    @endif
                </div>

                <div class="ac-bs-row__right">
                    <span class="ac-bs-row__amount {{ $row->is_abnormal ? 'ac-text-danger' : 'ac-text-success' }}">
                        {{ $row->is_abnormal ? '('.$fmt($row->net_balance).')' : $fmt($row->net_balance) }}
                    </span>
                    <span class="ac-bs-row__pct ac-text-muted">{{ $row->pct_of_assets }}%</span>
                    <div class="ac-bs-row__bar">
                        <div class="ac-progress-fill ac-progress-fill--green"
                             data-pct="{{ $row->pct_of_section }}"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="ac-bs-section__subtotal ac-bs-section__subtotal--green">
            <span>إجمالي الأصول</span>
            <span class="ac-bs-section__subtotal-amt ac-text-success">
                {{ number_format($total_assets, 2) }}
            </span>
        </div>

    </div>{{-- /assets --}}

    {{-- ── Liabilities ─────────────────────────────────────── --}}
    <div class="ac-bs-section ac-bs-section--liability">

        <div class="ac-bs-section__header ac-bs-section__header--red">
            <div class="ac-bs-section__dot ac-bs-section__dot--red"></div>
            <h3 class="ac-bs-section__title">الالتزامات</h3>
            <span class="ac-bs-section__badge ac-bs-section__badge--red">
                {{ $liabilities->count() }}
            </span>
        </div>

        @if($liabilities->isEmpty())
            <p class="ac-bs-empty">لا توجد التزامات مُسجَّلة.</p>
        @else
        <div class="ac-bs-col-header">
            <span class="ac-bs-col-header__name">الحساب</span>
            <span class="ac-bs-col-header__right">
                <span>الرصيد</span>
                <span>% الأصول</span>
            </span>
        </div>
        <div class="ac-bs-rows" id="bs-section-liabilities">
            @foreach($liabilities as $row)
            <div class="ac-bs-row ac-bs-row--depth-{{ min($row->depth, 4) }} {{ $row->is_parent ? 'ac-bs-row--parent' : '' }} {{ $row->is_abnormal ? 'ac-bs-row--abnormal' : '' }}"
                 data-bs-node="account-{{ $row->id }}"
                 @if($row->depth > 0) data-bs-parent="account-{{ $row->parent_id }}" @endif>

                <div class="ac-bs-row__info">
                    @if($row->is_parent)
                        <button type="button" class="ac-bs-toggle" aria-label="طيّ/بسط">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5">
                                <polyline points="6,9 12,15 18,9"/>
                            </svg>
                        </button>
                    @else
                        <span class="ac-bs-toggle-spacer"></span>
                    @endif
                    <span class="ac-bs-row__code">{{ $row->code }}</span>
                    <div class="ac-bs-row__name-wrap">
                        <span class="ac-bs-row__name">{{ $row->name }}</span>
                        @if($row->is_parent && abs($row->own_balance) > 0.001)
                            <span class="ac-bs-row__own-note">{{ $fmt($row->own_balance) }}</span>
                        @endif
                    </div>
                    @if($row->is_abnormal)
                        <span class="ac-bs-row__abnormal-tag">معكوس</span>
                    @endif
                </div>

                <div class="ac-bs-row__right">
                    <span class="ac-bs-row__amount {{ $row->is_abnormal ? 'ac-text-success' : 'ac-text-danger' }}">
                        {{ $row->is_abnormal ? '('.$fmt($row->net_balance).')' : $fmt($row->net_balance) }}
                    </span>
                    <span class="ac-bs-row__pct ac-text-muted">{{ $row->pct_of_assets }}%</span>
                    <div class="ac-bs-row__bar">
                        <div class="ac-progress-fill ac-progress-fill--red"
                             data-pct="{{ $row->pct_of_section }}"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="ac-bs-section__subtotal ac-bs-section__subtotal--red">
            <span>إجمالي الالتزامات</span>
            <span class="ac-bs-section__subtotal-amt ac-text-danger">
                {{ number_format($total_liabilities, 2) }}
            </span>
        </div>

    </div>{{-- /liabilities --}}

    {{-- ── Equity ───────────────────────────────────────────── --}}
    <div class="ac-bs-section ac-bs-section--equity">

        <div class="ac-bs-section__header ac-bs-section__header--orange">
            <div class="ac-bs-section__dot ac-bs-section__dot--orange"></div>
            <h3 class="ac-bs-section__title">حقوق الملكية</h3>
            <span class="ac-bs-section__badge ac-bs-section__badge--orange">
                {{ $equity->count() }}
            </span>
        </div>

        @if($equity->isEmpty())
            <p class="ac-bs-empty">لا توجد حسابات حقوق ملكية مُسجَّلة.</p>
        @else
        <div class="ac-bs-col-header">
            <span class="ac-bs-col-header__name">الحساب</span>
            <span class="ac-bs-col-header__right">
                <span>الرصيد</span>
                <span>% الأصول</span>
            </span>
        </div>
        <div class="ac-bs-rows" id="bs-section-equity">
            @foreach($equity as $row)
            <div class="ac-bs-row ac-bs-row--depth-{{ min($row->depth, 4) }} {{ $row->is_parent ? 'ac-bs-row--parent' : '' }} {{ $row->is_abnormal ? 'ac-bs-row--abnormal' : '' }}"
                 data-bs-node="account-{{ $row->id }}"
                 @if($row->depth > 0) data-bs-parent="account-{{ $row->parent_id }}" @endif>

                <div class="ac-bs-row__info">
                    @if($row->is_parent)
                        <button type="button" class="ac-bs-toggle" aria-label="طيّ/بسط">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5">
                                <polyline points="6,9 12,15 18,9"/>
                            </svg>
                        </button>
                    @else
                        <span class="ac-bs-toggle-spacer"></span>
                    @endif
                    <span class="ac-bs-row__code">{{ $row->code }}</span>
                    <div class="ac-bs-row__name-wrap">
                        <span class="ac-bs-row__name">{{ $row->name }}</span>
                        @if($row->is_parent && abs($row->own_balance) > 0.001)
                            <span class="ac-bs-row__own-note">{{ $fmt($row->own_balance) }}</span>
                        @endif
                    </div>
                    @if($row->is_abnormal)
                        <span class="ac-bs-row__abnormal-tag">معكوس</span>
                    @endif
                </div>

                <div class="ac-bs-row__right">
                    <span class="ac-bs-row__amount {{ $row->is_abnormal ? 'ac-text-danger' : 'ac-text-warning' }}">
                        {{ $row->is_abnormal ? '('.$fmt($row->net_balance).')' : $fmt($row->net_balance) }}
                    </span>
                    <span class="ac-bs-row__pct ac-text-muted">{{ $row->pct_of_assets }}%</span>
                    <div class="ac-bs-row__bar">
                        <div class="ac-progress-fill ac-progress-fill--orange"
                             data-pct="{{ $row->pct_of_section }}"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="ac-bs-section__subtotal ac-bs-section__subtotal--orange">
            <span>إجمالي حقوق الملكية</span>
            <span class="ac-bs-section__subtotal-amt ac-text-warning">
                {{ $total_equity < 0 ? '('.$fmt($total_equity).')' : number_format($total_equity, 2) }}
            </span>
        </div>

        {{-- Liabilities + Equity combined --}}
        <div class="ac-bs-section__subtotal ac-bs-section__subtotal--combined">
            <span>الالتزامات + حقوق الملكية</span>
            <span class="ac-bs-section__subtotal-amt">
                {{ number_format($total_liabilities_and_equity, 2) }}
            </span>
        </div>

    </div>{{-- /equity --}}

</div>{{-- /ac-bs-grid-3 --}}

{{-- ════════════════════════════════════════════════════════════
     Equation footer
     ════════════════════════════════════════════════════════════ --}}
<div class="ac-bs-equation {{ $is_balanced ? 'ac-bs-equation--balanced' : 'ac-bs-equation--unbalanced' }}">

    <div class="ac-bs-equation__side">
        <div class="ac-bs-equation__label">الأصول</div>
        <div class="ac-bs-equation__value ac-text-success">
            {{ number_format($total_assets, 2) }}
        </div>
    </div>

    <div class="ac-bs-equation__op">{{ $is_balanced ? '=' : '≠' }}</div>

    <div class="ac-bs-equation__side">
        <div class="ac-bs-equation__label">الالتزامات + حقوق الملكية</div>
        <div class="ac-bs-equation__value">
            {{ number_format($total_liabilities_and_equity, 2) }}
        </div>
    </div>

    <div class="ac-bs-equation__badge {{ $is_balanced ? 'ac-bs-equation__badge--ok' : 'ac-bs-equation__badge--err' }}">
        @if($is_balanced)
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            ✅ الميزانية متوازنة
        @else
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            ⚠️ الميزانية غير متوازنة — فرق {{ number_format($difference, 2) }}
        @endif
    </div>

</div>

@endif{{-- /not empty --}}

@endsection

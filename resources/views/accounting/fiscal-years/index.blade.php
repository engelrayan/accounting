@extends('accounting._layout')

@section('title', 'إغلاق السنة المالية')

@php
    $fmt = fn(float $n) => number_format(abs($n), 2);
@endphp

@section('topbar-actions')
    <div class="ac-topbar-actions">
        <a href="{{ route('accounting.reports.profit-loss') }}"
           class="ac-btn ac-btn--secondary ac-btn--sm">أرباح وخسائر</a>
        <a href="{{ route('accounting.reports.balance-sheet') }}"
           class="ac-btn ac-btn--secondary ac-btn--sm">الميزانية</a>
    </div>
@endsection

@section('content')

{{-- ── Hero ─────────────────────────────────────────────────────────────── --}}
<div class="ac-fy-hero">
    <div class="ac-fy-hero__content">
        <span class="ac-fy-hero__eyebrow">نهاية الدورة المحاسبية</span>
        <h2 class="ac-fy-hero__title">إغلاق السنة المالية</h2>
        <p class="ac-fy-hero__text">
            يُرحِّل قيد الإقفال أرصدة الإيرادات والمصروفات إلى حساب الأرباح المحتجزة،
            ويُغلق الفترة تماماً ويمنع أي قيود مستقبلية عليها.
        </p>
    </div>
    <div class="ac-fy-hero__icon">📅</div>
</div>

{{-- ── Flash messages ───────────────────────────────────────────────────── --}}
@if (session('success'))
    <div class="ac-alert ac-alert--success">{{ session('success') }}</div>
@endif
@if (session('info'))
    <div class="ac-alert ac-alert--info">{{ session('info') }}</div>
@endif
@if ($errors->has('close'))
    <div class="ac-alert ac-alert--danger">{{ $errors->first('close') }}</div>
@endif
@if ($errors->has('year'))
    <div class="ac-alert ac-alert--danger">{{ $errors->first('year') }}</div>
@endif
@if ($errors->has('reopen'))
    <div class="ac-alert ac-alert--danger">{{ $errors->first('reopen') }}</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════════
     OPEN YEAR
     ══════════════════════════════════════════════════════════════════════════ --}}

@if ($openYear)

<div class="ac-fy-open-card">

    {{-- Header --}}
    <div class="ac-fy-open-header">
        <div class="ac-fy-open-title">
            <span>📂</span>
            السنة المالية {{ $openYear->year }}
            <span class="ac-fy-open-badge">مفتوحة</span>
        </div>
        <div style="font-size:.85rem;opacity:.85;">
            {{ $openYear->starts_at->format('Y/m/d') }}
            &nbsp;—&nbsp;
            {{ $openYear->ends_at->format('Y/m/d') }}
        </div>
    </div>

    {{-- Body --}}
    <div class="ac-fy-open-body">

        {{-- P&L Preview --}}
        @if ($preview)
        <div class="ac-fy-preview">

            {{-- Revenues --}}
            <div class="ac-fy-preview__section">
                <div class="ac-fy-preview__section-title ac-fy-preview__section-title--revenue">
                    الإيرادات
                </div>
                @forelse ($preview['revenues'] as $acct)
                    <div class="ac-fy-acct-row">
                        <span class="ac-fy-acct-name">{{ $acct->name }}</span>
                        <span class="ac-fy-acct-amount ac-fy-acct-amount--revenue">
                            {{ $fmt($acct->balance) }}
                        </span>
                    </div>
                @empty
                    <div class="ac-fy-preview__empty">لا توجد إيرادات مسجلة في هذه الفترة.</div>
                @endforelse
                @if ($preview['revenues']->isNotEmpty())
                    <div class="ac-fy-acct-row" style="font-weight:700;margin-top:.5rem;border-top:2px solid #bbf7d0;padding-top:.5rem;">
                        <span>الإجمالي</span>
                        <span class="ac-fy-acct-amount--revenue">{{ $fmt($preview['totalRevenue']) }}</span>
                    </div>
                @endif
            </div>

            {{-- Expenses --}}
            <div class="ac-fy-preview__section">
                <div class="ac-fy-preview__section-title ac-fy-preview__section-title--expense">
                    المصروفات
                </div>
                @forelse ($preview['expenses'] as $acct)
                    <div class="ac-fy-acct-row">
                        <span class="ac-fy-acct-name">{{ $acct->name }}</span>
                        <span class="ac-fy-acct-amount ac-fy-acct-amount--expense">
                            {{ $fmt($acct->balance) }}
                        </span>
                    </div>
                @empty
                    <div class="ac-fy-preview__empty">لا توجد مصروفات مسجلة في هذه الفترة.</div>
                @endforelse
                @if ($preview['expenses']->isNotEmpty())
                    <div class="ac-fy-acct-row" style="font-weight:700;margin-top:.5rem;border-top:2px solid #fecaca;padding-top:.5rem;">
                        <span>الإجمالي</span>
                        <span class="ac-fy-acct-amount--expense">{{ $fmt($preview['totalExpense']) }}</span>
                    </div>
                @endif
            </div>

        </div>{{-- /.ac-fy-preview --}}

        {{-- Net Result --}}
        @php
            $net       = $preview['netProfit'];
            $netClass  = $net > 0 ? 'profit' : ($net < 0 ? 'loss' : 'zero');
            $netIcon   = $net > 0 ? '📈' : ($net < 0 ? '📉' : '➖');
            $netLabel  = $net > 0 ? 'صافي الربح' : ($net < 0 ? 'صافي الخسارة' : 'التعادل');
        @endphp
        <div class="ac-fy-net ac-fy-net--{{ $netClass }}">
            <div>
                <div class="ac-fy-net__label">{{ $netIcon }} {{ $netLabel }} — {{ $openYear->year }}</div>
                <div style="font-size:.8rem;color:var(--ac-muted);margin-top:.25rem;">
                    سيُرحَّل هذا المبلغ إلى حساب الأرباح المحتجزة (3100) عند الإغلاق.
                </div>
            </div>
            <div class="ac-fy-net__value">
                @if ($net != 0) {{ $fmt($net) }} @else صفر @endif
            </div>
        </div>

        @endif{{-- /preview --}}

        {{-- Close button → opens confirm modal --}}
        <div style="display:flex;justify-content:flex-end;">
            <button type="button"
                    class="ac-btn ac-btn--danger"
                    onclick="document.getElementById('fy-close-modal').showModal()">
                🔒 إغلاق السنة المالية {{ $openYear->year }}
            </button>
        </div>

    </div>{{-- /.ac-fy-open-body --}}
</div>{{-- /.ac-fy-open-card --}}

{{-- ── Confirm close modal ──────────────────────────────────────────────── --}}
<dialog id="fy-close-modal" class="ac-modal">
    <div class="ac-modal__box" style="max-width:520px;">
        <div class="ac-modal__header">
            <h3 class="ac-modal__title">⚠️ تأكيد إغلاق السنة المالية {{ $openYear->year }}</h3>
            <button type="button" class="ac-modal__close"
                    onclick="document.getElementById('fy-close-modal').close()">✕</button>
        </div>
        <div class="ac-modal__body">
            <div class="ac-fy-confirm">
                <strong>هذا الإجراء لا يمكن التراجع عنه. سيتم:</strong>
                <ul>
                    <li>إنشاء قيد إقفال بتاريخ <strong>{{ $openYear->ends_at->format('Y/m/d') }}</strong> يُصفِّر أرصدة الإيرادات والمصروفات.</li>
                    <li>ترحيل صافي النتيجة إلى حساب <strong>الأرباح المحتجزة (3100)</strong>.</li>
                    <li>قفل الفترة من <strong>{{ $openYear->starts_at->format('Y/m/d') }}</strong> حتى <strong>{{ $openYear->ends_at->format('Y/m/d') }}</strong> — لن يُقبَل أي قيد بتاريخ في هذه الفترة بعد اليوم.</li>
                </ul>
            </div>
            @if ($preview && $preview['netProfit'] >= 0)
                <p style="font-size:.9rem;color:var(--ac-text);">
                    سيُرحَّل مبلغ
                    <strong class="ac-fy-profit">{{ $fmt($preview['netProfit']) }}</strong>
                    كربح إلى الأرباح المحتجزة.
                </p>
            @elseif ($preview && $preview['netProfit'] < 0)
                <p style="font-size:.9rem;color:var(--ac-text);">
                    سيُرحَّل مبلغ
                    <strong class="ac-fy-loss">{{ $fmt($preview['netProfit']) }}</strong>
                    كخسارة من الأرباح المحتجزة.
                </p>
            @endif
        </div>
        <div class="ac-modal__footer">
            <button type="button"
                    class="ac-btn ac-btn--secondary"
                    onclick="document.getElementById('fy-close-modal').close()">
                إلغاء
            </button>
            <form method="POST"
                  action="{{ route('accounting.fiscal-years.close', $openYear) }}"
                  style="display:inline;">
                @csrf
                <button type="submit" class="ac-btn ac-btn--danger">
                    🔒 نعم، أغلق السنة المالية
                </button>
            </form>
        </div>
    </div>
</dialog>

@else

{{-- No open year → show create form ──────────────────────────────────────── --}}
<div class="ac-fy-create-card">
    <h3 style="margin:0 0 1rem;font-size:1rem;font-weight:700;">
        ➕ فتح سنة مالية جديدة
    </h3>
    <p style="font-size:.875rem;color:var(--ac-muted);margin-bottom:1.25rem;">
        لا توجد سنة مالية مفتوحة. أنشئ سنة جديدة لتبدأ تسجيل القيود عليها.
    </p>
    <form method="POST" action="{{ route('accounting.fiscal-years.store') }}"
          style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;align-items:end;">
        @csrf

        <div class="ac-form-group" style="margin:0;">
            <label class="ac-label">السنة</label>
            <input type="number" name="year" value="{{ old('year', $suggestYear) }}"
                   min="2000" max="2100" class="ac-input" required>
        </div>

        <div class="ac-form-group" style="margin:0;">
            <label class="ac-label">تاريخ البداية</label>
            <input type="date" name="starts_at"
                   value="{{ old('starts_at', $suggestYear . '-01-01') }}"
                   class="ac-input" required>
        </div>

        <div class="ac-form-group" style="margin:0;">
            <label class="ac-label">تاريخ النهاية</label>
            <input type="date" name="ends_at"
                   value="{{ old('ends_at', $suggestYear . '-12-31') }}"
                   class="ac-input" required>
        </div>

        <div>
            <button type="submit" class="ac-btn ac-btn--primary" style="width:100%;">
                فتح السنة المالية
            </button>
        </div>
    </form>
</div>

@endif{{-- /openYear --}}


{{-- ══════════════════════════════════════════════════════════════════════════
     HISTORY — closed years
     ══════════════════════════════════════════════════════════════════════════ --}}

<div class="ac-card">
    <div class="ac-card__header">
        <h3 class="ac-card__title">سجل السنوات المالية المغلقة</h3>
    </div>
    <div class="ac-card__body" style="padding:0;">

        @if ($closedYears->isEmpty())
            <div style="padding:2rem;text-align:center;color:var(--ac-muted);font-size:.9rem;">
                لا توجد سنوات مغلقة بعد.
            </div>
        @else
        <div style="overflow-x:auto;">
        <table class="ac-fy-history-table">
            <thead>
                <tr>
                    <th>السنة</th>
                    <th>من</th>
                    <th>إلى</th>
                    <th>صافي النتيجة</th>
                    <th>قيد الإقفال</th>
                    <th>تاريخ الإغلاق</th>
                    <th>الإجراء</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($closedYears as $fy)
                    @php
                        $np = $fy->net_profit ?? 0;
                        $cls = $np > 0 ? 'ac-fy-profit' : ($np < 0 ? 'ac-fy-loss' : 'ac-fy-zero');
                        $npLabel = $np > 0
                            ? ('ربح ' . number_format($np, 2))
                            : ($np < 0 ? ('خسارة ' . number_format(abs($np), 2)) : 'تعادل');
                    @endphp
                    <tr>
                        <td><strong>{{ $fy->year }}</strong></td>
                        <td>{{ $fy->starts_at->format('Y/m/d') }}</td>
                        <td>{{ $fy->ends_at->format('Y/m/d') }}</td>
                        <td><span class="{{ $cls }}">{{ $npLabel }}</span></td>
                        <td>
                            @if ($fy->closing_entry_id)
                                <a href="{{ route('accounting.journal-entries.show', $fy->closing_entry_id) }}"
                                   class="ac-link" style="font-size:.8rem;">
                                    عرض القيد
                                </a>
                            @else
                                <span style="color:var(--ac-muted);font-size:.8rem;">لا يوجد</span>
                            @endif
                        </td>
                        <td style="font-size:.82rem;color:var(--ac-muted);">
                            {{ $fy->closed_at?->format('Y/m/d H:i') ?? '—' }}
                        </td>
                        <td>
                            @can('can-write')
                                <form method="POST"
                                      action="{{ route('accounting.fiscal-years.reopen', $fy) }}"
                                      onsubmit="return confirm('سيتم إعادة فتح السنة المالية {{ $fy->year }}. هل تريد المتابعة؟');">
                                    @csrf
                                    <button type="submit" class="ac-btn ac-btn--ghost ac-btn--sm">
                                        إعادة فتح
                                    </button>
                                </form>
                            @else
                                <span style="color:var(--ac-muted);font-size:.8rem;">—</span>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif

    </div>{{-- /.ac-card__body --}}
</div>{{-- /.ac-card --}}

@endsection

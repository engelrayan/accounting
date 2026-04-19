@extends('accounting._layout')

@section('title', 'كشف حساب — ' . $customer->name)

@php
$fmt       = fn(float $n) => number_format($n, 2);
$fromHuman = \Carbon\Carbon::parse($from)->locale('ar')->translatedFormat('j F Y');
$toHuman   = \Carbon\Carbon::parse($to)->locale('ar')->translatedFormat('j F Y');
$baseQuery = ['from' => $from, 'to' => $to];
@endphp

@section('topbar-actions')
    <div class="ac-topbar-actions">
        <a href="{{ route('accounting.customers.show', $customer->id) }}"
           class="ac-btn ac-btn--secondary ac-btn--sm">صفحة العميل</a>
        <button type="button" class="ac-btn ac-btn--secondary ac-btn--sm"
                onclick="window.print()">طباعة</button>
        <a href="{{ route('accounting.reports.customer-statement', array_merge([$customer->id], ['from' => $from, 'to' => $to, 'export' => 'pdf'])) }}"
           class="ac-btn ac-btn--primary ac-btn--sm">PDF</a>
    </div>
@endsection

@section('content')

{{-- ── Customer Header ─────────────────────────────────────────────────── --}}
<div class="ac-stmt-header">
    <div class="ac-stmt-header__customer">
        <div class="ac-cust-avatar ac-cust-avatar--lg">{{ $customer->initial() }}</div>
        <div>
            <h2 class="ac-stmt-header__name">{{ $customer->name }}</h2>
            <div class="ac-stmt-header__meta">
                @if($customer->phone)
                    <span>{{ $customer->phone }}</span>
                @endif
                @if($customer->email)
                    <span>{{ $customer->email }}</span>
                @endif
                @if($customer->address)
                    <span>{{ $customer->address }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="ac-stmt-header__period">
        <span class="ac-stmt-header__period-label">الفترة</span>
        <strong>{{ $fromHuman }}</strong>
        <span class="ac-stmt-header__period-sep">—</span>
        <strong>{{ $toHuman }}</strong>
    </div>
</div>

{{-- ── Filter ───────────────────────────────────────────────────────────── --}}
<div class="ac-card ac-report-filter-card ac-print-hide">
    <div class="ac-card__body">
        <form method="GET" action="{{ route('accounting.reports.customer-statement', $customer->id) }}"
              class="ac-report-filter">
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
                    <a href="{{ route('accounting.reports.customer-statement', $customer->id) }}"
                       class="ac-btn ac-btn--secondary">هذا الشهر</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ── Summary cards ────────────────────────────────────────────────────── --}}
<div class="ac-dash-grid ac-dash-grid--4" style="margin-bottom:24px">
    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">الرصيد الافتتاحي</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="16"/>
                    <line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $opening_balance > 0 ? 'ac-text-danger' : ($opening_balance < 0 ? 'ac-text-success' : '') }}">
            {{ $fmt(abs($opening_balance)) }}
        </div>
        <div class="ac-dash-card__footer">
            @if($opening_balance > 0) مدين
            @elseif($opening_balance < 0) دائن
            @else مُسوَّى @endif
        </div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي الفواتير</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-danger">{{ $fmt($total_debit) }}</div>
        <div class="ac-dash-card__footer">
            {{ $transactions->where('type', 'invoice')->count() }} فاتورة في الفترة
        </div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي الدفعات</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-success">{{ $fmt($total_credit) }}</div>
        <div class="ac-dash-card__footer">
            {{ $transactions->where('type', 'payment')->count() }} دفعة في الفترة
        </div>
    </div>

    <div class="ac-dash-card {{ $closing_balance > 0 ? 'ac-dash-card--alert' : '' }}">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">الرصيد الختامي</span>
            <div class="ac-dash-card__icon {{ $closing_balance > 0 ? 'ac-dash-card__icon--red' : 'ac-dash-card__icon--green' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $closing_balance > 0 ? 'ac-text-danger' : 'ac-text-success' }}">
            {{ $fmt(abs($closing_balance)) }}
        </div>
        <div class="ac-dash-card__footer">
            @if($closing_balance > 0) مستحق على العميل
            @elseif($closing_balance < 0) رصيد دائن للعميل
            @else حساب مسوَّى @endif
        </div>
    </div>
</div>

{{-- ── Statement Table ──────────────────────────────────────────────────── --}}
<div class="ac-card">
    <div class="ac-stmt-table-wrap">
        <table class="ac-table ac-stmt-table">
            <thead>
                <tr>
                    <th class="ac-stmt-col-date">التاريخ</th>
                    <th class="ac-stmt-col-ref">المرجع</th>
                    <th class="ac-stmt-col-desc">البيان</th>
                    <th class="ac-stmt-col-num">مدين (فاتورة)</th>
                    <th class="ac-stmt-col-num">دائن (دفعة)</th>
                    <th class="ac-stmt-col-balance">الرصيد</th>
                </tr>
            </thead>
            <tbody>

                {{-- Opening balance row --}}
                <tr class="ac-stmt-opening-row">
                    <td class="ac-stmt-cell-date">{{ $fromHuman }}</td>
                    <td colspan="2" class="ac-stmt-cell-ref">رصيد افتتاحي</td>
                    <td class="ac-stmt-cell-num">—</td>
                    <td class="ac-stmt-cell-num">—</td>
                    <td class="ac-stmt-cell-balance {{ $opening_balance > 0 ? 'ac-text-danger' : ($opening_balance < 0 ? 'ac-text-success' : 'ac-text-muted') }}">
                        {{ $fmt($opening_balance) }}
                    </td>
                </tr>

                @forelse($transactions as $txn)
                    <tr class="ac-stmt-row ac-stmt-row--{{ $txn->type }}">
                        <td class="ac-stmt-cell-date">
                            {{ \Carbon\Carbon::parse($txn->event_date)->format('Y-m-d') }}
                        </td>
                        <td class="ac-stmt-cell-ref">
                            @if($txn->type === 'invoice')
                                <a href="{{ route('accounting.invoices.index') }}"
                                   class="ac-stmt-ref-link">{{ $txn->reference }}</a>
                            @else
                                <span class="ac-stmt-payment-ref">{{ $txn->reference }}</span>
                            @endif
                        </td>
                        <td class="ac-stmt-cell-desc">{{ $txn->description ?: '—' }}</td>
                        <td class="ac-stmt-cell-num">
                            @if($txn->debit > 0)
                                <span class="ac-stmt-debit">{{ $fmt($txn->debit) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="ac-stmt-cell-num">
                            @if($txn->credit > 0)
                                <span class="ac-stmt-credit">{{ $fmt($txn->credit) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="ac-stmt-cell-balance {{ $txn->balance > 0 ? 'ac-text-danger' : ($txn->balance < 0 ? 'ac-text-success' : 'ac-text-muted') }}">
                            {{ $fmt($txn->balance) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="ac-stmt-empty">
                            لا توجد حركات في هذه الفترة.
                        </td>
                    </tr>
                @endforelse

            </tbody>
            <tfoot>
                {{-- Totals row --}}
                <tr class="ac-stmt-totals-row">
                    <td colspan="3" class="ac-stmt-totals-label">إجمالي الفترة</td>
                    <td class="ac-stmt-cell-num">
                        <strong class="ac-text-danger">{{ $fmt($total_debit) }}</strong>
                    </td>
                    <td class="ac-stmt-cell-num">
                        <strong class="ac-text-success">{{ $fmt($total_credit) }}</strong>
                    </td>
                    <td></td>
                </tr>
                {{-- Closing balance row --}}
                <tr class="ac-stmt-closing-row">
                    <td colspan="5" class="ac-stmt-closing-label">الرصيد الختامي بتاريخ {{ $toHuman }}</td>
                    <td class="ac-stmt-cell-balance ac-stmt-closing-val {{ $closing_balance > 0 ? 'ac-text-danger' : ($closing_balance < 0 ? 'ac-text-success' : 'ac-text-muted') }}">
                        <strong>{{ $fmt($closing_balance) }}</strong>
                        @if($closing_balance > 0)
                            <span class="ac-stmt-closing-badge ac-stmt-closing-badge--debit">مدين</span>
                        @elseif($closing_balance < 0)
                            <span class="ac-stmt-closing-badge ac-stmt-closing-badge--credit">دائن</span>
                        @else
                            <span class="ac-stmt-closing-badge ac-stmt-closing-badge--zero">مُسوَّى</span>
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection

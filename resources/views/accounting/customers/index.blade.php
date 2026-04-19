@extends('accounting._layout')

@section('title', 'العملاء')

@section('topbar-actions')
    @can('can-write')
    <a href="{{ route('accounting.customers.create') }}" class="ac-btn ac-btn--primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        عميل جديد
    </a>
    @endcan
@endsection

@section('content')

{{-- ── Summary Cards ─────────────────────────────────────────────────────── --}}
<div class="ac-dash-grid">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">عدد العملاء</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $customers->count() }}</div>
        <div class="ac-dash-card__footer">عميل مسجّل</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي الذمم</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $totalReceivable > 0 ? 'ac-text-danger' : '' }}">
            {{ number_format($totalReceivable, 2) }}
        </div>
        <div class="ac-dash-card__footer">مجموع الأرصدة المستحقة</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">عملاء بذمم</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $withBalanceCount }}</div>
        <div class="ac-dash-card__footer">لديهم رصيد مستحق</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">عملاء مسوّيون</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $settledCount }}</div>
        <div class="ac-dash-card__footer">رصيدهم صفر أو أقل</div>
    </div>

</div>

{{-- ── Filter Bar ──────────────────────────────────────────────────────────── --}}
<div class="ac-cust-filter-bar">
    <a href="{{ route('accounting.customers.index') }}"
       class="ac-btn ac-btn--sm {{ !$filterOutstanding ? 'ac-btn--primary' : 'ac-btn--secondary' }}">
        الكل ({{ $customers->count() + ($filterOutstanding ? $settledCount : 0) }})
    </a>
    <a href="{{ route('accounting.customers.index', ['outstanding' => 1]) }}"
       class="ac-btn ac-btn--sm {{ $filterOutstanding ? 'ac-btn--danger' : 'ac-btn--secondary' }}">
        بذمم فقط
        @if($withBalanceCount > 0)
            <span class="ac-count-pill ac-count-pill--danger">{{ $withBalanceCount }}</span>
        @endif
    </a>
</div>

{{-- ── Table ──────────────────────────────────────────────────────────────── --}}
@if($customers->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
        <p>{{ $filterOutstanding ? 'لا يوجد عملاء بذمم مستحقة.' : 'لا يوجد عملاء بعد.' }}</p>
        @if(!$filterOutstanding)
            @can('can-write')
                <a href="{{ route('accounting.customers.create') }}" class="ac-btn ac-btn--primary">أضف أول عميل</a>
            @endcan
        @else
            <a href="{{ route('accounting.customers.index') }}" class="ac-btn ac-btn--secondary">عرض الكل</a>
        @endif
    </div>
@else

<div class="ac-card">
    <div class="ac-card__body" style="padding:0;">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>العميل</th>
                    <th>الهاتف</th>
                    <th class="ac-table__num">الفواتير</th>
                    <th class="ac-table__num">إجمالي الفواتير</th>
                    <th class="ac-table__num">المحصَّل</th>
                    <th class="ac-table__num">الرصيد المستحق</th>
                    <th>الحالة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                <tr class="{{ $customer->balance > 0 ? 'ac-row--has-balance' : '' }}">
                    <td>
                        <div class="ac-cust-cell">
                            <div class="ac-cust-avatar {{ $customer->balance > 0 ? 'ac-cust-avatar--debt' : '' }}">
                                {{ $customer->initial() }}
                            </div>
                            <div class="ac-cust-info">
                                <span class="ac-cust-name">{{ $customer->name }}</span>
                                @if($customer->email)
                                    <span class="ac-cust-sub">{{ $customer->email }}</span>
                                @elseif($customer->address)
                                    <span class="ac-cust-sub">{{ Str::limit($customer->address, 38) }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="ac-table__muted">{{ $customer->phone ?: '—' }}</td>
                    <td class="ac-table__num">{{ $customer->invoices_count }}</td>
                    <td class="ac-table__num">
                        {{ number_format($customer->totalInvoiced ?? 0, 2) }}
                    </td>
                    <td class="ac-table__num ac-text-success">
                        {{ number_format(max(0, ($customer->totalInvoiced ?? 0) - max(0, $customer->balance)), 2) }}
                    </td>
                    <td class="ac-table__num">
                        @if($customer->balance > 0)
                            <span class="ac-balance-amount ac-balance-amount--debt">
                                {{ number_format($customer->balance, 2) }}
                            </span>
                        @elseif($customer->balance < 0)
                            <span class="ac-balance-amount ac-balance-amount--credit">
                                {{ number_format(abs($customer->balance), 2) }}
                            </span>
                        @else
                            <span class="ac-table__muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($customer->balance <= 0)
                            <span class="ac-badge ac-badge--posted">مسوَّى</span>
                        @else
                            <span class="ac-badge ac-badge--reversed">مستحق</span>
                        @endif
                    </td>
                    <td class="ac-row-actions">
                        <a href="{{ route('accounting.customers.show', $customer) }}"
                           class="ac-btn ac-btn--ghost ac-btn--sm">عرض</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
            @if($customers->count() > 1)
            <tfoot>
                <tr class="ac-table__foot-total">
                    <td colspan="5" class="ac-font-bold">إجمالي الذمم</td>
                    <td class="ac-table__num ac-font-bold {{ $totalReceivable > 0 ? 'ac-text-danger' : '' }}">
                        {{ number_format($totalReceivable, 2) }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@endif
@endsection

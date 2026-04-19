@extends('accounting._layout')

@section('title', 'الموردون')

@section('topbar-actions')
    @can('can-write')
    <a href="{{ route('accounting.vendors.create') }}" class="ac-btn ac-btn--primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        مورد جديد
    </a>
    @endcan
@endsection

@section('content')

{{-- ── Summary Cards ─────────────────────────────────────────────────────── --}}
<div class="ac-dash-grid">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">عدد الموردين</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $vendors->count() }}</div>
        <div class="ac-dash-card__footer">مورد مسجّل</div>
    </div>

    <div class="ac-dash-card {{ $totalPayable > 0 ? 'ac-dash-card--unbalanced' : '' }}">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي الذمم الدائنة</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $totalPayable > 0 ? 'ac-text-danger' : '' }}">
            {{ number_format($totalPayable, 2) }}
        </div>
        <div class="ac-dash-card__footer">مجموع الأرصدة المستحقة</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">موردون بذمم</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $withBalanceCount > 0 ? 'ac-text-danger' : '' }}">
            {{ $withBalanceCount }}
        </div>
        <div class="ac-dash-card__footer">لديهم أرصدة مستحقة</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">حسابات مسوّاة</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20,6 9,17 4,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-success">{{ $settledCount }}</div>
        <div class="ac-dash-card__footer">لا ذمم مستحقة</div>
    </div>

</div>

{{-- ── Filter Bar ───────────────────────────────────────────────────────── --}}
<div class="ac-filter-bar" style="margin-bottom:16px">
    <a href="{{ route('accounting.vendors.index') }}"
       class="ac-filter-tag {{ ! $filterOutstanding ? 'ac-filter-tag--active' : '' }}">
        الكل ({{ $vendors->count() }})
    </a>
    <a href="{{ route('accounting.vendors.index', ['outstanding' => 1]) }}"
       class="ac-filter-tag {{ $filterOutstanding ? 'ac-filter-tag--active' : '' }}">
        بذمم ({{ $withBalanceCount }})
    </a>
    <a href="{{ route('accounting.reports.ap-aging') }}" class="ac-filter-tag">
        تقرير التقادم
    </a>
</div>

{{-- ── Table ────────────────────────────────────────────────────────────── --}}
@if($vendors->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
        </svg>
        <div>
            <p style="font-weight:700;margin:0">لا يوجد موردون</p>
            <p style="font-size:13px;color:var(--ac-text-muted);margin:4px 0 0">
                ابدأ بإضافة مورد جديد.
            </p>
        </div>
        @can('can-write')
        <a href="{{ route('accounting.vendors.create') }}" class="ac-btn ac-btn--primary">إضافة مورد</a>
        @endcan
    </div>
@else
    <div class="ac-card">
        <div style="overflow-x:auto">
            <table class="ac-table">
                <thead>
                    <tr>
                        <th>المورد</th>
                        <th>الهاتف</th>
                        <th>عدد الفواتير</th>
                        <th style="text-align:left">إجمالي المشتريات</th>
                        <th style="text-align:left">الرصيد المستحق</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendors as $vendor)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div class="ac-cust-avatar">{{ $vendor->initial() }}</div>
                                <div>
                                    <a href="{{ route('accounting.vendors.show', $vendor) }}"
                                       class="ac-table-link">{{ $vendor->name }}</a>
                                    @if($vendor->email)
                                        <div style="font-size:11px;color:var(--ac-muted)">{{ $vendor->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $vendor->phone ?? '—' }}</td>
                        <td style="text-align:center">
                            <span class="ac-badge">{{ $vendor->purchase_invoices_count }}</span>
                        </td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums">
                            {{ number_format($vendor->totalInvoiced, 2) }}
                        </td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums">
                            @if($vendor->balance > 0)
                                <span class="ac-badge ac-badge--danger">{{ number_format($vendor->balance, 2) }}</span>
                            @elseif($vendor->balance < 0)
                                <span class="ac-badge ac-badge--success">{{ number_format(abs($vendor->balance), 2) }} دائن</span>
                            @else
                                <span class="ac-badge ac-badge--success">مسوّى</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('accounting.vendors.show', $vendor) }}"
                               class="ac-btn ac-btn--secondary ac-btn--sm">عرض</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection

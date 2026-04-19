@extends('accounting._layout')

@section('title', 'مسيرات الرواتب')

@section('topbar-actions')
    @can('can-write')
    <a href="{{ route('accounting.payroll.create') }}" class="ac-btn ac-btn--primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        مسير رواتب جديد
    </a>
    @endcan
@endsection

@section('content')

@include('accounting._flash')

{{-- ── بطاقة ملخص ──────────────────────────────────────────────────────── --}}
<div class="ac-dash-grid" style="--ac-dash-cols:3">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي المصرف</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($totalPaid, 2) }}</div>
        <div class="ac-dash-card__footer">صافي رواتب مصروفة</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">عدد المسيرات</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $runs->total() }}</div>
        <div class="ac-dash-card__footer">مسير مسجّل</div>
    </div>

</div>

{{-- ── جدول المسيرات ───────────────────────────────────────────────────── --}}
@if($runs->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        <div>
            <p style="font-weight:700;margin:0">لا توجد مسيرات رواتب بعد</p>
            <p style="font-size:13px;color:var(--ac-text-muted);margin:4px 0 0">أضف الموظفين أولاً ثم أنشئ مسير رواتب شهري.</p>
        </div>
        @can('can-write')
        <a href="{{ route('accounting.payroll.create') }}" class="ac-btn ac-btn--primary">إنشاء أول مسير</a>
        @endcan
    </div>
@else
    <div class="ac-card">
        <div style="overflow-x:auto">
            <table class="ac-table">
                <thead>
                    <tr>
                        <th>الفترة</th>
                        <th style="text-align:left">راتب أساسي</th>
                        <th style="text-align:left">بدلات</th>
                        <th style="text-align:left">خصومات</th>
                        <th style="text-align:left">صافي</th>
                        <th style="text-align:center">الحالة</th>
                        <th>تاريخ الاعتماد</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($runs as $run)
                    <tr>
                        <td style="font-weight:700">{{ $run->periodLabel() }}</td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums">{{ number_format($run->total_basic, 2) }}</td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums;color:var(--ac-success)">
                            {{ $run->total_allowances > 0 ? '+'.number_format($run->total_allowances, 2) : '—' }}
                        </td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums;color:var(--ac-danger)">
                            {{ $run->total_deductions > 0 ? '-'.number_format($run->total_deductions, 2) : '—' }}
                        </td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums;font-weight:700">
                            {{ number_format($run->total_net, 2) }}
                        </td>
                        <td style="text-align:center">
                            <span class="ac-badge {{ $run->statusBadgeClass() }}">{{ $run->statusLabel() }}</span>
                        </td>
                        <td style="font-size:.82rem;color:var(--ac-text-muted)">
                            {{ $run->approved_at ? $run->approved_at->format('Y-m-d') : '—' }}
                        </td>
                        <td>
                            <a href="{{ route('accounting.payroll.show', $run) }}"
                               class="ac-btn ac-btn--secondary ac-btn--sm">عرض</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div style="margin-top:16px">{{ $runs->links() }}</div>
@endif

@endsection

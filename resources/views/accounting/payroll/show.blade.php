@extends('accounting._layout')

@section('title', 'مسير رواتب ' . $payrollRun->periodLabel())

@section('topbar-actions')
    <div style="display:flex;gap:8px">
        <a href="{{ route('accounting.payroll.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>

        @if($payrollRun->isDraft())
        @can('can-write')
        <form method="POST" action="{{ route('accounting.payroll.approve', $payrollRun) }}"
              onsubmit="return confirm('سيتم اعتماد المسير وتسجيل قيد محاسبي. هل أنت متأكد؟')">
            @csrf
            <button type="submit" class="ac-btn ac-btn--primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20,6 9,17 4,12"/>
                </svg>
                اعتماد وصرف الرواتب
            </button>
        </form>
        @endcan
        @endif
    </div>
@endsection

@section('content')

@include('accounting._flash')

@if($errors->has('approve'))
    <div class="ac-alert ac-alert--error">{{ $errors->first('approve') }}</div>
@endif

{{-- ── ترويسة المسير ───────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
    <span class="ac-badge {{ $payrollRun->statusBadgeClass() }}" style="font-size:.9rem;padding:4px 12px">
        {{ $payrollRun->statusLabel() }}
    </span>
    @if($payrollRun->approved_at)
        <span style="font-size:.82rem;color:var(--ac-text-muted)">
            اعتمد في {{ $payrollRun->approved_at->format('Y-m-d H:i') }}
            @if($payrollRun->approvedBy) بواسطة {{ $payrollRun->approvedBy->name }} @endif
        </span>
    @endif
    @if($payrollRun->journal_entry_id)
        <a href="{{ route('accounting.journal-entries.show', $payrollRun->journal_entry_id) }}"
           class="ac-btn ac-btn--ghost ac-btn--sm" style="margin-right:auto">عرض القيد المحاسبي</a>
    @endif
</div>

{{-- ── بطاقات الملخص ──────────────────────────────────────────────────── --}}
<div class="ac-dash-grid">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي الراتب الأساسي</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($payrollRun->total_basic, 2) }}</div>
        <div class="ac-dash-card__footer">{{ $payrollRun->lines->count() }} موظف</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي البدلات</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-success">+{{ number_format($payrollRun->total_allowances, 2) }}</div>
        <div class="ac-dash-card__footer">بدلات مضافة</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي الخصومات</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-danger">-{{ number_format($payrollRun->total_deductions, 2) }}</div>
        <div class="ac-dash-card__footer">خصومات ومستقطعات</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">صافي الرواتب</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount" style="font-size:1.6rem;font-weight:800">
            {{ number_format($payrollRun->total_net, 2) }}
        </div>
        <div class="ac-dash-card__footer">المبلغ المصروف فعلاً</div>
    </div>

</div>

{{-- ── جدول الموظفين ───────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin:24px 0 12px">
    <h2 style="font-size:1rem;font-weight:700;margin:0">تفاصيل الموظفين</h2>
    @if($payrollRun->isDraft())
    <span style="font-size:.8rem;color:var(--ac-text-muted)">انقر "تعديل" لإضافة بدلات أو خصومات لأي موظف</span>
    @endif
</div>

<div class="ac-card">
    <div style="overflow-x:auto">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>القسم</th>
                    <th style="text-align:left">الأساسي</th>
                    <th style="text-align:left">البدلات</th>
                    <th style="text-align:left">الإجمالي</th>
                    <th style="text-align:left">الخصومات</th>
                    <th style="text-align:left;font-weight:800">الصافي</th>
                    <th style="text-align:center">الدفع</th>
                    @if($payrollRun->isDraft()) <th></th> @endif
                </tr>
            </thead>
            <tbody>
                @foreach($payrollRun->lines as $line)
                <tr>
                    <td>
                        <div style="font-weight:600">{{ $line->employee->name ?? '—' }}</div>
                        <div style="font-size:.75rem;color:var(--ac-text-muted)">{{ $line->employee->employee_number ?? '' }}</div>
                    </td>
                    <td style="color:var(--ac-text-muted);font-size:.82rem">{{ $line->employee->department ?? '—' }}</td>
                    <td style="text-align:left;font-variant-numeric:tabular-nums">{{ number_format($line->basic_salary, 2) }}</td>
                    <td style="text-align:left;font-variant-numeric:tabular-nums;color:var(--ac-success)">
                        @if($line->totalAllowances() > 0)
                            <span title="{{ collect($line->allowances)->map(fn($a) => $a['name'].': '.number_format($a['amount'],2))->join(' | ') }}">
                                +{{ number_format($line->totalAllowances(), 2) }}
                            </span>
                        @else
                            <span style="color:var(--ac-text-muted)">—</span>
                        @endif
                    </td>
                    <td style="text-align:left;font-variant-numeric:tabular-nums">{{ number_format($line->gross_salary, 2) }}</td>
                    <td style="text-align:left;font-variant-numeric:tabular-nums;color:var(--ac-danger)">
                        @if($line->totalDeductions() > 0)
                            <span title="{{ collect($line->deductions)->map(fn($d) => $d['name'].': '.number_format($d['amount'],2))->join(' | ') }}">
                                -{{ number_format($line->totalDeductions(), 2) }}
                            </span>
                        @else
                            <span style="color:var(--ac-text-muted)">—</span>
                        @endif
                    </td>
                    <td style="text-align:left;font-variant-numeric:tabular-nums;font-weight:800;font-size:1rem">
                        {{ number_format($line->net_salary, 2) }}
                    </td>
                    <td style="text-align:center">
                        <span class="ac-badge ac-badge--muted" style="font-size:.75rem">{{ $line->paymentMethodLabel() }}</span>
                    </td>
                    @if($payrollRun->isDraft())
                    <td>
                        @can('can-write')
                        <a href="{{ route('accounting.payroll.edit-line', [$payrollRun, $line]) }}"
                           class="ac-btn ac-btn--ghost ac-btn--sm">تعديل</a>
                        @endcan
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight:800;background:var(--ac-bg)">
                    <td colspan="2" style="text-align:center;color:var(--ac-text-muted)">المجموع</td>
                    <td style="text-align:left;font-variant-numeric:tabular-nums">{{ number_format($payrollRun->total_basic, 2) }}</td>
                    <td style="text-align:left;font-variant-numeric:tabular-nums;color:var(--ac-success)">
                        {{ $payrollRun->total_allowances > 0 ? '+'.number_format($payrollRun->total_allowances, 2) : '—' }}
                    </td>
                    <td style="text-align:left;font-variant-numeric:tabular-nums">{{ number_format($payrollRun->total_gross, 2) }}</td>
                    <td style="text-align:left;font-variant-numeric:tabular-nums;color:var(--ac-danger)">
                        {{ $payrollRun->total_deductions > 0 ? '-'.number_format($payrollRun->total_deductions, 2) : '—' }}
                    </td>
                    <td style="text-align:left;font-variant-numeric:tabular-nums;font-size:1.1rem">
                        {{ number_format($payrollRun->total_net, 2) }}
                    </td>
                    <td colspan="{{ $payrollRun->isDraft() ? 2 : 1 }}"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection

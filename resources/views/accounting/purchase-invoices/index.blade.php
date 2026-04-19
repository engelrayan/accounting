@extends('accounting._layout')

@section('title', 'فواتير المشتريات')

@section('topbar-actions')
    @can('can-write')
    <a href="{{ route('accounting.purchase-invoices.create') }}" class="ac-btn ac-btn--primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        فاتورة جديدة
    </a>
    @endcan
@endsection

@section('content')

{{-- ── Status Tabs ──────────────────────────────────────────────────────── --}}
<div class="ac-filter-bar" style="margin-bottom:16px">
    <a href="{{ route('accounting.purchase-invoices.index') }}"
       class="ac-filter-tag {{ !$status ? 'ac-filter-tag--active' : '' }}">
        الكل ({{ $counts->sum() }})
    </a>
    @foreach(['pending' => 'معلقة', 'partial' => 'جزئي', 'paid' => 'مدفوعة', 'cancelled' => 'ملغاة'] as $s => $label)
    <a href="{{ route('accounting.purchase-invoices.index', ['status' => $s]) }}"
       class="ac-filter-tag {{ $status === $s ? 'ac-filter-tag--active' : '' }}">
        {{ $label }} ({{ $counts[$s] ?? 0 }})
    </a>
    @endforeach
</div>

@if($invoices->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14,2 14,8 20,8"/>
        </svg>
        <div>
            <p style="font-weight:700;margin:0">لا توجد فواتير</p>
            <p style="font-size:13px;color:var(--ac-text-muted);margin:4px 0 0">
                @if($status) لا توجد فواتير بهذه الحالة. @else ابدأ بإنشاء فاتورة مشتريات جديدة. @endif
            </p>
        </div>
        @can('can-write')
        <a href="{{ route('accounting.purchase-invoices.create') }}" class="ac-btn ac-btn--primary">إنشاء فاتورة</a>
        @endcan
    </div>
@else
    <div class="ac-card">
        <div style="overflow-x:auto">
            <table class="ac-table">
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>المورد</th>
                        <th>تاريخ الإصدار</th>
                        <th>تاريخ الاستحقاق</th>
                        <th class="ac-table__num">الإجمالي</th>
                        <th class="ac-table__num">المدفوع</th>
                        <th class="ac-table__num">المتبقي</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                    <tr class="{{ $invoice->isOverdue() ? 'ac-row--overdue' : ($invoice->isUnpaid() ? 'ac-row--unpaid' : '') }}">
                        <td class="ac-text-mono ac-font-bold">{{ $invoice->invoice_number }}</td>
                        <td>
                            <a href="{{ route('accounting.vendors.show', $invoice->vendor_id) }}"
                               class="ac-table-link">{{ $invoice->vendor->name }}</a>
                        </td>
                        <td class="ac-table__muted">{{ $invoice->issue_date->format('Y-m-d') }}</td>
                        <td>
                            @if($invoice->due_date)
                                <span class="{{ $invoice->isOverdue() ? 'ac-text-danger' : 'ac-table__muted' }}">
                                    {{ $invoice->due_date->format('Y-m-d') }}
                                    @if($invoice->isOverdue())
                                        <span class="ac-overdue-tag">متأخرة</span>
                                    @endif
                                </span>
                            @else
                                <span class="ac-table__muted">—</span>
                            @endif
                        </td>
                        <td class="ac-table__num">{{ number_format($invoice->amount, 2) }}</td>
                        <td class="ac-table__num ac-text-success">{{ number_format($invoice->totalPaid(), 2) }}</td>
                        <td class="ac-table__num {{ $invoice->remaining() > 0 ? 'ac-text-danger ac-font-bold' : '' }}">
                            {{ number_format($invoice->remaining(), 2) }}
                        </td>
                        <td>
                            <div class="ac-cust-inv-status">
                                <span class="ac-badge ac-badge--{{ $invoice->statusMod() }}">{{ $invoice->statusLabel() }}</span>
                                @if($invoice->status !== 'paid' && (float)$invoice->amount > 0 && $invoice->totalPaid() > 0)
                                    <div class="ac-progress-bar ac-progress-bar--xs">
                                        <div class="ac-progress-fill" data-pct="{{ $invoice->paidPct() }}"></div>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('accounting.purchase-invoices.show', $invoice) }}"
                               class="ac-btn ac-btn--secondary ac-btn--sm">عرض</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:16px">
        {{ $invoices->links() }}
    </div>
@endif

@endsection

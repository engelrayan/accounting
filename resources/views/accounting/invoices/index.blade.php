@extends('accounting._layout')

@section('title', 'الفواتير')

@section('topbar-actions')
    @can('can-write')
    <a href="{{ route('accounting.invoices.create') }}" class="ac-btn ac-btn--primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        فاتورة جديدة
    </a>
    @endcan
@endsection

@section('content')

{{-- ── Status filter tabs ─────────────────────────────────────────────────── --}}
<div class="ac-inv-tabs">
    <a href="{{ route('accounting.invoices.index') }}"
       class="ac-inv-tab {{ !$status ? 'ac-inv-tab--active' : '' }}">
        الكل
        <span class="ac-inv-tab__count">{{ $counts->sum() }}</span>
    </a>
    <a href="{{ route('accounting.invoices.index', ['status' => 'pending']) }}"
       class="ac-inv-tab {{ $status === 'pending' ? 'ac-inv-tab--active' : '' }}">
        معلقة
        <span class="ac-inv-tab__count ac-inv-tab__count--pending">{{ $counts['pending'] ?? 0 }}</span>
    </a>
    <a href="{{ route('accounting.invoices.index', ['status' => 'partial']) }}"
       class="ac-inv-tab {{ $status === 'partial' ? 'ac-inv-tab--active' : '' }}">
        جزئي
        <span class="ac-inv-tab__count ac-inv-tab__count--partial">{{ $counts['partial'] ?? 0 }}</span>
    </a>
    <a href="{{ route('accounting.invoices.index', ['status' => 'paid']) }}"
       class="ac-inv-tab {{ $status === 'paid' ? 'ac-inv-tab--active' : '' }}">
        مدفوعة
        <span class="ac-inv-tab__count ac-inv-tab__count--paid">{{ $counts['paid'] ?? 0 }}</span>
    </a>
</div>

{{-- ── Table ──────────────────────────────────────────────────────────────── --}}
@if($invoices->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14,2 14,8 20,8"/>
        </svg>
        <p>لا توجد فواتير{{ $status ? ' بهذه الحالة' : '' }}.</p>
        @can('can-write')
            <a href="{{ route('accounting.invoices.create') }}" class="ac-btn ac-btn--primary">أنشئ أول فاتورة</a>
        @endcan
    </div>
@else

<div class="ac-card">
    <div class="ac-card__body" style="padding:0;">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>العميل</th>
                    <th class="ac-table__num">المبلغ</th>
                    <th class="ac-table__num">المدفوع</th>
                    <th class="ac-table__num">المتبقي</th>
                    <th>الحالة</th>
                    <th>تاريخ الإصدار</th>
                    <th>الاستحقاق</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                <tr class="{{ $invoice->isOverdue() ? 'ac-table__row--warn' : '' }}">
                    <td class="ac-text-mono ac-font-bold">
                        {{ $invoice->invoice_number }}
                    </td>
                    <td>
                        <div class="ac-cust-cell">
                            <div class="ac-cust-avatar ac-cust-avatar--sm">{{ $invoice->customer->initial() }}</div>
                            <span>{{ $invoice->customer->name }}</span>
                        </div>
                    </td>
                    <td class="ac-table__num">{{ number_format($invoice->amount, 2) }}</td>
                    <td class="ac-table__num ac-text-success">{{ number_format($invoice->paid_amount, 2) }}</td>
                    <td class="ac-table__num {{ $invoice->remaining_amount > 0 ? 'ac-text-danger ac-font-bold' : '' }}">
                        {{ number_format($invoice->remaining_amount, 2) }}
                    </td>
                    <td>
                        <span class="ac-badge ac-badge--{{ $invoice->statusMod() }}">
                            {{ $invoice->statusLabel() }}
                        </span>
                        @if($invoice->isOverdue())
                            <span class="ac-badge ac-badge--reversed">متأخرة</span>
                        @endif
                    </td>
                    <td class="ac-table__muted">{{ $invoice->issue_date->format('Y-m-d') }}</td>
                    <td class="ac-table__muted {{ $invoice->isOverdue() ? 'ac-text-danger' : '' }}">
                        {{ $invoice->due_date?->format('Y-m-d') ?? '—' }}
                    </td>
                    <td class="ac-row-actions">
                        <a href="{{ route('accounting.invoices.show', $invoice) }}"
                           class="ac-btn ac-btn--ghost ac-btn--sm">عرض</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($invoices->hasPages())
    <div class="ac-pagination">{{ $invoices->links() }}</div>
@endif

@endif
@endsection

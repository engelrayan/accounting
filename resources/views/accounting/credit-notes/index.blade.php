@extends('accounting._layout')

@section('title', 'الإشعارات الدائنة')

@section('topbar-actions')
<div class="ac-topbar-actions">
    <a href="{{ route('accounting.credit-notes.create') }}" class="ac-btn ac-btn--primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        إشعار دائن جديد
    </a>
</div>
@endsection

@section('content')

@if(session('success'))
    <div class="ac-alert ac-alert--success">{{ session('success') }}</div>
@endif

<div class="ac-card">
    <div class="ac-card__body">

        @if($creditNotes->isEmpty())
            <div class="ac-empty-state">
                <div class="ac-empty-state__icon">📋</div>
                <p class="ac-empty-state__text">لا توجد إشعارات دائنة حتى الآن.</p>
                <a href="{{ route('accounting.credit-notes.create') }}" class="ac-btn ac-btn--primary">إنشاء أول إشعار</a>
            </div>
        @else
            <div class="ac-table-wrap">
                <table class="ac-table">
                    <thead>
                        <tr>
                            <th>رقم الإشعار</th>
                            <th>العميل</th>
                            <th>الفاتورة المرجعية</th>
                            <th>التاريخ</th>
                            <th>المبلغ</th>
                            <th>الضريبة</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($creditNotes as $cn)
                        <tr>
                            <td class="ac-table__mono">{{ $cn->credit_note_number }}</td>
                            <td>{{ $cn->customer?->name ?? '—' }}</td>
                            <td class="ac-table__mono">
                                @if($cn->invoice)
                                    <a href="{{ route('accounting.invoices.show', $cn->invoice_id) }}"
                                       class="ac-link">{{ $cn->invoice->invoice_number }}</a>
                                @else —
                                @endif
                            </td>
                            <td>{{ $cn->issue_date->format('Y/m/d') }}</td>
                            <td class="ac-table__num">{{ number_format($cn->amount, 2) }}</td>
                            <td class="ac-table__num">{{ number_format($cn->tax_amount, 2) }}</td>
                            <td class="ac-table__num ac-text-danger">{{ number_format($cn->total, 2) }}</td>
                            <td>
                                <span class="ac-badge ac-badge--{{ $cn->statusMod() }}">{{ $cn->statusLabel() }}</span>
                            </td>
                            <td>
                                <a href="{{ route('accounting.credit-notes.show', $cn) }}"
                                   class="ac-btn ac-btn--secondary ac-btn--xs">عرض</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ac-pagination">
                {{ $creditNotes->links() }}
            </div>
        @endif

    </div>
</div>

@endsection

@extends('accounting._layout')

@section('title', 'التسوية البنكية')

@section('topbar-actions')
    <a href="{{ route('accounting.bank-reconciliation.create') }}" class="ac-btn ac-btn--primary ac-btn--sm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        كشف بنكي جديد
    </a>
@endsection

@section('content')

<div class="ac-card">
    <div class="ac-card__body" style="padding:0">

        @if($statements->isEmpty())
            <div class="ac-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
                <p>لا توجد تسويات بنكية حتى الآن.</p>
                <a href="{{ route('accounting.bank-reconciliation.create') }}" class="ac-btn ac-btn--primary ac-btn--sm">
                    ابدأ أول تسوية
                </a>
            </div>
        @else
            <table class="ac-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الحساب</th>
                        <th>تاريخ الكشف</th>
                        <th>الرصيد الافتتاحي</th>
                        <th>الرصيد الختامي</th>
                        <th>الحالة</th>
                        <th>تاريخ التسوية</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($statements as $stmt)
                    <tr>
                        <td class="ac-muted">{{ $stmt->id }}</td>
                        <td>
                            <span class="ac-code">{{ $stmt->account->code }}</span>
                            {{ $stmt->account->name }}
                        </td>
                        <td>{{ $stmt->statement_date->format('Y-m-d') }}</td>
                        <td class="ac-num">{{ number_format($stmt->opening_balance, 2) }}</td>
                        <td class="ac-num">{{ number_format($stmt->closing_balance, 2) }}</td>
                        <td>
                            <span class="ac-badge ac-badge--{{ $stmt->statusMod() }}">
                                {{ $stmt->statusLabel() }}
                            </span>
                        </td>
                        <td class="ac-muted">
                            {{ $stmt->reconciled_at ? $stmt->reconciled_at->format('Y-m-d') : '—' }}
                        </td>
                        <td>
                            <a href="{{ route('accounting.bank-reconciliation.show', $stmt) }}"
                               class="ac-btn ac-btn--ghost ac-btn--sm">
                                {{ $stmt->isReconciled() ? 'عرض' : 'مطابقة' }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($statements->hasPages())
                <div style="padding:1rem 1.5rem">
                    {{ $statements->links() }}
                </div>
            @endif
        @endif

    </div>
</div>

@endsection

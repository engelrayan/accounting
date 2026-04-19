@extends('accounting._layout')

@section('title', 'المعاملات')

@section('topbar-actions')
    @can('can-write')
    <a href="{{ route('accounting.transactions.create') }}" class="ac-btn ac-btn--primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        معاملة جديدة
    </a>
    @endcan
@endsection

@section('content')

@if($transactions->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
        </svg>
        <p>لا توجد معاملات بعد.</p>
        @can('can-write')
            <a href="{{ route('accounting.transactions.create') }}" class="ac-btn ac-btn--primary">سجّل أول معاملة</a>
        @endcan
    </div>
@else

<div class="ac-card">
    <div class="ac-card__body" style="padding:0;">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>النوع</th>
                    <th>الوصف</th>
                    <th>من</th>
                    <th>إلى</th>
                    <th class="ac-table__num">المبلغ</th>
                    <th>التاريخ</th>
                    <th>بواسطة</th>
                    <th class="ac-table__att">مرفق</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $tx)
                <tr>
                    <td>
                        <span class="ac-txn-badge ac-txn-badge--{{ $tx->type }}">
                            {{ \App\Modules\Accounting\Models\Transaction::typeLabel($tx->type) }}
                        </span>
                    </td>
                    <td class="ac-table__muted">{{ $tx->description ?: '—' }}</td>
                    <td>
                        <span class="ac-account-chip">{{ $tx->fromAccount->name }}</span>
                    </td>
                    <td>
                        <span class="ac-account-chip">{{ $tx->toAccount->name }}</span>
                    </td>
                    <td class="ac-table__num ac-table__amount">
                        {{ number_format($tx->amount, 2) }}
                    </td>
                    <td class="ac-table__muted">{{ $tx->transaction_date->format('Y-m-d') }}</td>
                    <td>
                        @if($tx->creator)
                            <div class="ac-created-by">
                                <div class="ac-created-by__avatar ac-created-by__avatar--{{ $tx->creator->roleClass() }}">
                                    {{ mb_substr($tx->creator->name, 0, 1) }}
                                </div>
                                <div class="ac-created-by__info">
                                    <span class="ac-created-by__name">{{ $tx->creator->name }}</span>
                                    <span class="ac-role-badge ac-role-badge--xs ac-role-badge--{{ $tx->creator->roleClass() }}">
                                        {{ $tx->creator->roleName() }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <span class="ac-table__muted">—</span>
                        @endif
                    </td>
                    <td class="ac-table__att">
                        @if($tx->attachments->isNotEmpty())
                            <div class="ac-att-cell ac-att-cell--stack">
                                @foreach($tx->attachments as $att)
                                    @if($att->isImage())
                                        <a href="{{ route('accounting.attachments.show', $att) }}"
                                           class="ac-att-badge ac-att-badge--image"
                                           target="_blank"
                                           title="{{ $att->file_name }}">
                                            <span class="ac-att-badge__icon">🖼</span>
                                        </a>
                                    @elseif($att->file_type === 'pdf')
                                        <a href="{{ route('accounting.attachments.download', $att) }}"
                                           class="ac-att-badge ac-att-badge--pdf"
                                           title="{{ $att->file_name }}">
                                            <span class="ac-att-badge__icon">📄</span>
                                        </a>
                                    @else
                                        <a href="{{ route('accounting.attachments.download', $att) }}"
                                           class="ac-att-badge ac-att-badge--excel"
                                           title="{{ $att->file_name }}">
                                            <span class="ac-att-badge__icon">📊</span>
                                        </a>
                                    @endif
                                @endforeach
                                @if($tx->attachments->count() > 1)
                                    <span class="ac-att-count">{{ $tx->attachments->count() }}</span>
                                @endif
                            </div>
                        @else
                            <span class="ac-table__muted">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($transactions->hasPages())
    <div class="ac-pagination">
        {{ $transactions->links() }}
    </div>
@endif

@endif
@endsection

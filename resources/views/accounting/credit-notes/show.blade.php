@extends('accounting._layout')

@section('title', 'إشعار دائن ' . $creditNote->credit_note_number)

@section('topbar-actions')
<div class="ac-inv-topbar-actions">
    <a href="{{ route('accounting.credit-notes.pdf', $creditNote) }}"
       class="ac-btn ac-btn--primary ac-no-print" target="_blank">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="12" y1="18" x2="12" y2="12"/>
            <line x1="9" y1="15" x2="15" y2="15"/>
        </svg>
        تصدير PDF
    </a>
    <button onclick="window.print()" class="ac-btn ac-btn--secondary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6,9 6,2 18,2 18,9"/>
            <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
            <rect x="6" y="14" width="12" height="8"/>
        </svg>
        طباعة
    </button>
    <a href="{{ route('accounting.credit-notes.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>
@endsection

@section('content')

@if(session('success'))
    <div class="ac-alert ac-alert--success ac-no-print">{{ session('success') }}</div>
@endif

<div class="ac-cn-show-layout">

    {{-- ════════════════════════════════════════════════════════════════════
         PRINTABLE CREDIT NOTE CARD
         ════════════════════════════════════════════════════════════════════ --}}
    <div class="ac-inv-doc ac-cn-doc" id="cn-print">

        {{-- Header --}}
        <div class="ac-inv-doc__header">
            <div class="ac-inv-doc__brand">
                <div class="ac-inv-doc__brand-icon ac-cn-doc__icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="9" y1="15" x2="15" y2="15"/>
                        <line x1="9" y1="11" x2="15" y2="11"/>
                    </svg>
                </div>
                <div>
                    <div class="ac-inv-doc__company">محاسب عام</div>
                    <div class="ac-inv-doc__company-sub">نظام المحاسبة المتكامل</div>
                </div>
            </div>
            <div class="ac-inv-doc__meta">
                <div class="ac-cn-doc__title">إشعار دائن</div>
                <div class="ac-inv-doc__number">{{ $creditNote->credit_note_number }}</div>
                <span class="ac-badge ac-badge--{{ $creditNote->statusMod() }}">{{ $creditNote->statusLabel() }}</span>
            </div>
        </div>

        {{-- Info row: customer + dates --}}
        <div class="ac-inv-doc__info-row">
            <div class="ac-inv-doc__party">
                <div class="ac-inv-doc__party-label">بيانات العميل</div>
                <div class="ac-inv-doc__party-name">{{ $creditNote->customer?->name ?? '—' }}</div>
                @if($creditNote->customer?->phone)
                    <div class="ac-inv-doc__party-detail">{{ $creditNote->customer->phone }}</div>
                @endif
                @if($creditNote->customer?->email)
                    <div class="ac-inv-doc__party-detail">{{ $creditNote->customer->email }}</div>
                @endif
            </div>
            <div class="ac-inv-doc__dates">
                <div class="ac-inv-doc__date-row">
                    <span class="ac-inv-doc__date-label">تاريخ الإشعار</span>
                    <span class="ac-inv-doc__date-val">{{ $creditNote->issue_date->format('Y/m/d') }}</span>
                </div>
                <div class="ac-inv-doc__date-row">
                    <span class="ac-inv-doc__date-label">الفاتورة المرجعية</span>
                    <span class="ac-inv-doc__date-val ac-table__mono">
                        {{ $creditNote->invoice?->invoice_number ?? '—' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- سبب الإشعار --}}
        @if($creditNote->reason)
        <div class="ac-cn-doc__reason">
            <span class="ac-cn-doc__reason-label">السبب:</span>
            <span class="ac-cn-doc__reason-text">{{ $creditNote->reason }}</span>
        </div>
        @endif

        {{-- Items from the original invoice --}}
        @if($creditNote->invoice?->items->isNotEmpty())
        <table class="ac-inv-doc__items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>البيان</th>
                    <th class="ac-text-center">الكمية</th>
                    <th class="ac-text-end">سعر الوحدة</th>
                    <th class="ac-text-end">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($creditNote->invoice->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="ac-text-center">{{ $item->quantity }}</td>
                    <td class="ac-text-end ac-table__mono">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="ac-text-end ac-table__mono">{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Totals --}}
        <div class="ac-inv-doc__footer">
            <div class="ac-inv-doc__totals">
                <div class="ac-inv-doc__total-row">
                    <span>مبلغ الإشعار</span>
                    <span class="ac-table__mono">{{ number_format($creditNote->amount, 2) }}</span>
                </div>
                @if((float) $creditNote->tax_amount > 0)
                <div class="ac-inv-doc__total-row">
                    <span>الضريبة ({{ number_format($creditNote->invoice?->tax_rate ?? 0, 2) }}%)</span>
                    <span class="ac-table__mono">{{ number_format($creditNote->tax_amount, 2) }}</span>
                </div>
                @endif
                <div class="ac-inv-doc__total-row ac-inv-doc__total-row--grand">
                    <span>إجمالي الإشعار الدائن</span>
                    <span class="ac-table__mono ac-text-danger">{{ number_format($creditNote->total, 2) }}</span>
                </div>
            </div>
        </div>

    </div>{{-- .ac-cn-doc --}}

    {{-- ════════════════════════════════════════════════════════════════════
         Sidebar: invoice link & meta
         ════════════════════════════════════════════════════════════════════ --}}
    <div class="ac-cn-show-sidebar ac-no-print">

        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">الفاتورة الأصلية</p>
                @if($creditNote->invoice)
                <div class="ac-cn-inv-meta">
                    <div class="ac-cn-inv-meta__row">
                        <span class="ac-cn-inv-meta__key">رقم الفاتورة</span>
                        <span class="ac-cn-inv-meta__val ac-table__mono">
                            <a href="{{ route('accounting.invoices.show', $creditNote->invoice_id) }}"
                               class="ac-link">{{ $creditNote->invoice->invoice_number }}</a>
                        </span>
                    </div>
                    <div class="ac-cn-inv-meta__row">
                        <span class="ac-cn-inv-meta__key">إجمالي الفاتورة</span>
                        <span class="ac-cn-inv-meta__val ac-table__mono">
                            {{ number_format($creditNote->invoice->amount, 2) }}
                        </span>
                    </div>
                    <div class="ac-cn-inv-meta__row">
                        <span class="ac-cn-inv-meta__key">المتبقي بعد الإشعار</span>
                        <span class="ac-cn-inv-meta__val ac-table__mono ac-text-danger">
                            {{ number_format($creditNote->invoice->remaining_amount, 2) }}
                        </span>
                    </div>
                    <div class="ac-cn-inv-meta__row">
                        <span class="ac-cn-inv-meta__key">حالة الفاتورة</span>
                        <span class="ac-badge ac-badge--{{ $creditNote->invoice->statusMod() }}">
                            {{ $creditNote->invoice->statusLabel() }}
                        </span>
                    </div>
                </div>
                @else
                <p class="ac-text-muted">—</p>
                @endif
            </div>
        </div>

        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">ملخص الإشعار</p>
                <div class="ac-cn-inv-meta">
                    <div class="ac-cn-inv-meta__row">
                        <span class="ac-cn-inv-meta__key">الرقم</span>
                        <span class="ac-cn-inv-meta__val ac-table__mono">{{ $creditNote->credit_note_number }}</span>
                    </div>
                    <div class="ac-cn-inv-meta__row">
                        <span class="ac-cn-inv-meta__key">الحالة</span>
                        <span class="ac-badge ac-badge--{{ $creditNote->statusMod() }}">{{ $creditNote->statusLabel() }}</span>
                    </div>
                    <div class="ac-cn-inv-meta__row">
                        <span class="ac-cn-inv-meta__key">التاريخ</span>
                        <span class="ac-cn-inv-meta__val">{{ $creditNote->issue_date->format('Y/m/d') }}</span>
                    </div>
                    <div class="ac-cn-inv-meta__row">
                        <span class="ac-cn-inv-meta__key">الإجمالي</span>
                        <span class="ac-cn-inv-meta__val ac-table__mono ac-text-danger">
                            {{ number_format($creditNote->total, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>{{-- .ac-cn-show-layout --}}

@endsection

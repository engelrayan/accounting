@extends('accounting._layout')

@section('title', 'فاتورة مشتريات ' . $purchaseInvoice->invoice_number)

@section('topbar-actions')
<div class="ac-inv-topbar-actions">
    <a href="{{ route('accounting.purchase-invoices.pdf', $purchaseInvoice) }}"
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
    <a href="{{ route('accounting.purchase-invoices.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>
@endsection

@section('content')

@if(session('success'))
    <div class="ac-alert ac-alert--success ac-no-print">{{ session('success') }}</div>
@endif
@if($errors->has('payment'))
    <div class="ac-alert ac-alert--error ac-no-print">{{ $errors->first('payment') }}</div>
@endif

<div class="ac-inv-layout">

    {{-- ════════════════════════════════════════════════════════════════════
         PRINTABLE INVOICE CARD
         ═══════════════════════════════════════════════════════════════════ --}}
    <div class="ac-inv-doc" id="invoice-print">

        {{-- Header --}}
        <div class="ac-inv-doc__header">
            <div class="ac-inv-doc__brand">
                <div class="ac-inv-doc__brand-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <div>
                    <div class="ac-inv-doc__company">فاتورة مشتريات</div>
                    <div class="ac-inv-doc__company-sub">نظام المحاسبة المتكامل</div>
                </div>
            </div>
            <div class="ac-inv-doc__meta">
                <div class="ac-inv-doc__number">{{ $purchaseInvoice->invoice_number }}</div>
                <div class="ac-inv-doc__status">
                    <span class="ac-badge ac-badge--{{ $purchaseInvoice->statusMod() }} ac-badge--lg">
                        {{ $purchaseInvoice->statusLabel() }}
                    </span>
                    @if($purchaseInvoice->isOverdue())
                        <span class="ac-badge ac-badge--reversed ac-badge--lg">متأخرة</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="ac-inv-doc__divider"></div>

        {{-- Vendor + Dates --}}
        <div class="ac-inv-doc__info-grid">
            <div class="ac-inv-doc__to">
                <div class="ac-inv-doc__info-label">من المورد</div>
                <div class="ac-inv-doc__customer-name">{{ $purchaseInvoice->vendor->name }}</div>
                @if($purchaseInvoice->vendor->phone)
                    <div class="ac-inv-doc__customer-detail">{{ $purchaseInvoice->vendor->phone }}</div>
                @endif
                @if($purchaseInvoice->vendor->email)
                    <div class="ac-inv-doc__customer-detail">{{ $purchaseInvoice->vendor->email }}</div>
                @endif
                @if($purchaseInvoice->vendor->address)
                    <div class="ac-inv-doc__customer-detail">{{ $purchaseInvoice->vendor->address }}</div>
                @endif
            </div>
            <div class="ac-inv-doc__dates">
                <div class="ac-inv-doc__date-row">
                    <span class="ac-inv-doc__date-label">رقم فاتورة داخلي</span>
                    <span class="ac-inv-doc__date-val ac-text-mono">{{ $purchaseInvoice->invoice_number }}</span>
                </div>
                @if($purchaseInvoice->vendor_invoice_number)
                <div class="ac-inv-doc__date-row">
                    <span class="ac-inv-doc__date-label">رقم فاتورة المورد</span>
                    <span class="ac-inv-doc__date-val ac-text-mono">{{ $purchaseInvoice->vendor_invoice_number }}</span>
                </div>
                @endif
                <div class="ac-inv-doc__date-row">
                    <span class="ac-inv-doc__date-label">تاريخ الفاتورة</span>
                    <span class="ac-inv-doc__date-val">{{ $purchaseInvoice->issue_date->format('Y-m-d') }}</span>
                </div>
                @if($purchaseInvoice->due_date)
                <div class="ac-inv-doc__date-row">
                    <span class="ac-inv-doc__date-label">تاريخ الاستحقاق</span>
                    <span class="ac-inv-doc__date-val {{ $purchaseInvoice->isOverdue() ? 'ac-text-danger' : '' }}">
                        {{ $purchaseInvoice->due_date->format('Y-m-d') }}
                    </span>
                </div>
                @endif
            </div>
        </div>

        <div class="ac-inv-doc__divider"></div>

        {{-- Line Items --}}
        <table class="ac-inv-doc__items">
            <thead>
                <tr>
                    <th class="ac-inv-doc__items-desc">الوصف</th>
                    <th class="ac-inv-doc__items-num">الكمية</th>
                    <th class="ac-inv-doc__items-num">سعر الوحدة</th>
                    <th class="ac-inv-doc__items-num">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseInvoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="ac-inv-doc__items-num">{{ number_format($item->quantity, 3) }}</td>
                    <td class="ac-inv-doc__items-num">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="ac-inv-doc__items-num">{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="ac-inv-doc__divider"></div>

        {{-- Totals --}}
        <div class="ac-inv-doc__totals">
            <div class="ac-inv-doc__totals-row">
                <span>المجموع قبل الضريبة</span>
                <span>{{ number_format($purchaseInvoice->subtotal, 2) }}</span>
            </div>
            @if((float) $purchaseInvoice->tax_rate > 0)
            <div class="ac-inv-doc__totals-row">
                <span>الضريبة ({{ number_format($purchaseInvoice->tax_rate, 2) }}%)</span>
                <span>{{ number_format($purchaseInvoice->tax_amount, 2) }}</span>
            </div>
            @endif
            <div class="ac-inv-doc__totals-row ac-inv-doc__totals-row--grand">
                <span>الإجمالي</span>
                <strong>{{ number_format($purchaseInvoice->amount, 2) }}</strong>
            </div>
            @if($purchaseInvoice->paid_amount > 0)
            <div class="ac-inv-doc__totals-row ac-text-success">
                <span>المدفوع</span>
                <span>{{ number_format($purchaseInvoice->paid_amount, 2) }}</span>
            </div>
            @if($purchaseInvoice->remaining() > 0)
            <div class="ac-inv-doc__totals-row ac-text-danger">
                <span>المتبقي</span>
                <strong>{{ number_format($purchaseInvoice->remaining(), 2) }}</strong>
            </div>
            @endif
            @endif
        </div>

        @if($purchaseInvoice->notes)
        <div class="ac-inv-doc__divider"></div>
        <div class="ac-inv-doc__notes">
            <span class="ac-inv-doc__notes-label">ملاحظات:</span>
            {{ $purchaseInvoice->notes }}
        </div>
        @endif

    </div>{{-- .ac-inv-doc --}}

    {{-- ════════════════════════════════════════════════════════════════════
         SIDEBAR: PAYMENT PROGRESS + RECORD PAYMENT
         ═══════════════════════════════════════════════════════════════════ --}}
    <div class="ac-inv-sidebar ac-no-print">

        {{-- Progress --}}
        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">حالة الدفع</p>

                @if($purchaseInvoice->status === 'paid')
                    <div class="ac-inv-paid-badge">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        مدفوعة بالكامل
                    </div>
                @else
                    <div class="ac-inv-progress-wrap">
                        <div class="ac-inv-progress-header">
                            <span class="ac-inv-progress-label">{{ $purchaseInvoice->paidPct() }}% مدفوع</span>
                            <span class="ac-inv-progress-remaining ac-text-danger">
                                متبقي: {{ number_format($purchaseInvoice->remaining(), 2) }}
                            </span>
                        </div>
                        <div class="ac-progress-bar">
                            <div class="ac-progress-fill" data-pct="{{ $purchaseInvoice->paidPct() }}"></div>
                        </div>
                    </div>
                @endif

                <div class="ac-inv-sidebar-amounts">
                    <div>
                        <span class="ac-inv-sidebar-amounts__label">الإجمالي</span>
                        <span class="ac-inv-sidebar-amounts__val">{{ number_format($purchaseInvoice->amount, 2) }}</span>
                    </div>
                    <div>
                        <span class="ac-inv-sidebar-amounts__label">المدفوع</span>
                        <span class="ac-inv-sidebar-amounts__val ac-text-success">{{ number_format($purchaseInvoice->paid_amount, 2) }}</span>
                    </div>
                    <div>
                        <span class="ac-inv-sidebar-amounts__label">المتبقي</span>
                        <span class="ac-inv-sidebar-amounts__val {{ $purchaseInvoice->remaining() > 0 ? 'ac-text-danger ac-font-bold' : '' }}">
                            {{ number_format($purchaseInvoice->remaining(), 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Record payment --}}
        @if($purchaseInvoice->isUnpaid() && $purchaseInvoice->status !== 'cancelled')
        @can('can-write')
        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">تسجيل دفعة</p>
                <form method="POST"
                      action="{{ route('accounting.purchase-invoices.pay', $purchaseInvoice) }}">
                    @csrf

                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="pay_amount">المبلغ</label>
                        <input type="number" id="pay_amount" name="payment_amount"
                               step="0.01" min="0.01"
                               max="{{ $purchaseInvoice->remaining() }}"
                               class="ac-control ac-control--num {{ $errors->has('payment_amount') ? 'ac-control--error' : '' }}"
                               value="{{ old('payment_amount', number_format($purchaseInvoice->remaining(), 2, '.', '')) }}"
                               placeholder="0.00" required>
                        <span class="ac-field-hint">
                            الحد الأقصى: {{ number_format($purchaseInvoice->remaining(), 2) }}
                        </span>
                        @error('payment_amount') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="pay_date">تاريخ الدفع</label>
                        <input type="date" id="pay_date" name="payment_date"
                               class="ac-control {{ $errors->has('payment_date') ? 'ac-control--error' : '' }}"
                               value="{{ old('payment_date', today()->toDateString()) }}" required>
                        @error('payment_date') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required">طريقة الدفع</label>
                        <div class="ac-pay-method-grid">
                            @foreach([
                                ['cash',     '💵', 'نقداً'],
                                ['bank',     '🏦', 'بنكي'],
                                ['wallet',   '📱', 'محفظة'],
                                ['instapay', '⚡', 'إنستاباي'],
                                ['cheque',   '📝', 'شيك'],
                                ['card',     '💳', 'بطاقة'],
                            ] as [$val, $icon, $label])
                            <label class="ac-pay-method-card {{ old('payment_method', 'cash') === $val ? 'ac-pay-method-card--active' : '' }}">
                                <input type="radio" name="payment_method" value="{{ $val }}"
                                       {{ old('payment_method', 'cash') === $val ? 'checked' : '' }}>
                                <span class="ac-pay-method-card__icon">{{ $icon }}</span>
                                <span class="ac-pay-method-card__label">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('payment_method') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label" for="pay_notes">ملاحظات</label>
                        <input type="text" id="pay_notes" name="payment_notes"
                               class="ac-control" placeholder="اختياري"
                               value="{{ old('payment_notes') }}">
                    </div>

                    <button type="submit" class="ac-btn ac-btn--success ac-btn--full">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        تسجيل الدفعة
                    </button>
                </form>
            </div>
        </div>
        @endcan
        @endif

        {{-- Payment history --}}
        @if($payments->isNotEmpty())
        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">سجل الدفعات</p>
                @foreach($payments as $payment)
                <div class="ac-inv-payment-row">
                    <div class="ac-inv-payment-row__left">
                        <span class="ac-pay-method-pill ac-pay-method-pill--{{ $payment->payment_method }}">
                            {{ \App\Modules\Accounting\Models\PurchasePayment::methodIcon($payment->payment_method) }}
                            {{ \App\Modules\Accounting\Models\PurchasePayment::methodLabel($payment->payment_method) }}
                        </span>
                        <span class="ac-inv-payment-row__date ac-table__muted">
                            {{ \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') }}
                        </span>
                    </div>
                    <span class="ac-inv-payment-row__amount ac-text-danger">
                        −{{ number_format($payment->amount, 2) }}
                    </span>
                </div>
                @endforeach
                <div class="ac-inv-payments-total">
                    <span>إجمالي المدفوع</span>
                    <strong class="ac-text-danger">−{{ number_format($purchaseInvoice->paid_amount, 2) }}</strong>
                </div>
            </div>
        </div>
        @endif

    </div>{{-- .ac-inv-sidebar --}}

</div>{{-- .ac-inv-layout --}}

@endsection

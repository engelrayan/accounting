@extends('accounting._layout')

@section('title', 'فاتورة ' . $invoice->invoice_number)

@section('topbar-actions')
<div class="ac-inv-topbar-actions">
    @if($invoice->isPosSale())
    <a href="{{ route('accounting.pos.receipt', $invoice) }}"
       class="ac-btn ac-btn--secondary ac-no-print" target="_blank">
        إيصال POS
    </a>
    @endif
    @if($invoice->isUnpaid())
    <a href="{{ route('accounting.credit-notes.create', ['invoice_id' => $invoice->id]) }}"
       class="ac-btn ac-btn--warning ac-no-print">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="9" y1="15" x2="15" y2="15"/>
        </svg>
        إشعار دائن
    </a>
    @endif
    <a href="{{ route('accounting.invoices.pdf', $invoice) }}"
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
    <a href="{{ route('accounting.invoices.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
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
                    <div class="ac-inv-doc__company">محاسب عام</div>
                    <div class="ac-inv-doc__company-sub">نظام المحاسبة المتكامل</div>
                </div>
            </div>
            <div class="ac-inv-doc__meta">
                <div class="ac-inv-doc__number">{{ $invoice->invoice_number }}</div>
                <div class="ac-inv-doc__status">
                    <span class="ac-badge ac-badge--{{ $invoice->statusMod() }} ac-badge--lg">
                        {{ $invoice->statusLabel() }}
                    </span>
                    @if($invoice->isOverdue())
                        <span class="ac-badge ac-badge--reversed ac-badge--lg">متأخرة</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="ac-inv-doc__divider"></div>

        {{-- Customer + Dates --}}
        <div class="ac-inv-doc__info-grid">
            <div class="ac-inv-doc__to">
                <div class="ac-inv-doc__info-label">إلى</div>
                <div class="ac-inv-doc__customer-name">{{ $invoice->customer->name }}</div>
                @if($invoice->customer->phone)
                    <div class="ac-inv-doc__customer-detail">{{ $invoice->customer->phone }}</div>
                @endif
                @if($invoice->customer->email)
                    <div class="ac-inv-doc__customer-detail">{{ $invoice->customer->email }}</div>
                @endif
                @if($invoice->customer->address)
                    <div class="ac-inv-doc__customer-detail">{{ $invoice->customer->address }}</div>
                @endif
            </div>
            <div class="ac-inv-doc__dates">
                <div class="ac-inv-doc__date-row">
                    <span class="ac-inv-doc__date-label">تاريخ الإصدار</span>
                    <span class="ac-inv-doc__date-value">{{ $invoice->issue_date->format('Y/m/d') }}</span>
                </div>
                @if($invoice->due_date)
                <div class="ac-inv-doc__date-row">
                    <span class="ac-inv-doc__date-label {{ $invoice->isOverdue() ? 'ac-text-danger' : '' }}">
                        تاريخ الاستحقاق
                    </span>
                    <span class="ac-inv-doc__date-value {{ $invoice->isOverdue() ? 'ac-text-danger' : '' }}">
                        {{ $invoice->due_date->format('Y/m/d') }}
                    </span>
                </div>
                @endif
                @if($invoice->payment_method)
                <div class="ac-inv-doc__date-row">
                    <span class="ac-inv-doc__date-label">طريقة الدفع</span>
                    <span class="ac-inv-doc__date-value">{{ $invoice->paymentMethodLabel() }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Items table --}}
        <table class="ac-inv-doc__items">
            <thead>
                <tr>
                    <th class="ac-inv-doc__col-num">#</th>
                    <th>الوصف</th>
                    <th class="ac-inv-doc__col-num">الكمية</th>
                    <th class="ac-inv-doc__col-num">سعر الوحدة</th>
                    <th class="ac-inv-doc__col-num">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $i => $item)
                <tr>
                    <td class="ac-inv-doc__col-num ac-text-muted">{{ $i + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="ac-inv-doc__col-num">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
                    <td class="ac-inv-doc__col-num">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="ac-inv-doc__col-num ac-font-bold">{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals + QR --}}
        <div class="ac-inv-doc__footer">

            {{-- QR Code --}}
            <div class="ac-inv-doc__qr">
                <div id="invoice-qr"></div>
                <div class="ac-inv-doc__qr-label">مسح للتحقق</div>
            </div>

            {{-- Totals --}}
            <div class="ac-inv-doc__totals">
                @if((float)$invoice->tax_amount > 0)
                <div class="ac-inv-doc__total-row">
                    <span>المجموع قبل الضريبة</span>
                    <span>{{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                @if((float)$invoice->discount_amount > 0)
                <div class="ac-inv-doc__total-row">
                    <span>الخصم</span>
                    <span>-{{ number_format($invoice->discount_amount, 2) }}</span>
                </div>
                @endif
                <div class="ac-inv-doc__total-row">
                    <span>
                        @php
                            $s = app(\App\Modules\Accounting\Services\CompanySettingsService::class)
                                     ->forCompany($invoice->company_id);
                        @endphp
                        {{ $s->taxName() }}
                        ({{ rtrim(rtrim(number_format((float)$invoice->tax_rate, 2),'0'),'.') }}%)
                    </span>
                    <span>{{ number_format($invoice->tax_amount, 2) }}</span>
                </div>
                @endif
                @if((float)$invoice->tax_amount <= 0 && (float)$invoice->discount_amount > 0)
                <div class="ac-inv-doc__total-row">
                    <span>الإجمالي قبل الخصم</span>
                    <span>{{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                <div class="ac-inv-doc__total-row">
                    <span>الخصم</span>
                    <span>-{{ number_format($invoice->discount_amount, 2) }}</span>
                </div>
                @endif
                <div class="ac-inv-doc__total-row ac-inv-doc__total-row--grand">
                    <span>الإجمالي</span>
                    <span>{{ number_format($invoice->amount, 2) }}</span>
                </div>
                <div class="ac-inv-doc__total-row">
                    <span>المدفوع</span>
                    <span class="ac-text-success">{{ number_format($invoice->paid_amount, 2) }}</span>
                </div>
                <div class="ac-inv-doc__total-row ac-inv-doc__total-row--grand">
                    <span>المتبقي</span>
                    <span class="{{ $invoice->remaining_amount > 0 ? 'ac-text-danger' : 'ac-text-success' }}">
                        {{ number_format($invoice->remaining_amount, 2) }}
                    </span>
                </div>
                @if($invoice->remaining_amount > 0 && $invoice->status !== 'cancelled')
                <div class="ac-inv-doc__progress">
                    <div class="ac-progress-bar">
                        <div class="ac-progress-fill" data-pct="{{ $invoice->paidPct() }}"></div>
                    </div>
                    <span class="ac-text-sm ac-text-muted">{{ $invoice->paidPct() }}% مدفوع</span>
                </div>
                @endif
            </div>

        </div>

        @if($invoice->notes)
        <div class="ac-inv-doc__notes">
            <div class="ac-inv-doc__notes-label">ملاحظات</div>
            <div class="ac-inv-doc__notes-text">{{ $invoice->notes }}</div>
        </div>
        @endif

    </div>{{-- .ac-inv-doc --}}

    {{-- ════════════════════════════════════════════════════════════════════
         PAYMENT PANEL (hidden in print)
         ═══════════════════════════════════════════════════════════════════ --}}
    {{-- ── تسجيل سداد ── --}}
    @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
    @can('can-write')
    <div class="ac-inv-pay-panel ac-no-print">
        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">تسجيل سداد</p>

                @error('payment') <div class="ac-alert ac-alert--error">{{ $message }}</div> @enderror

                <form method="POST" action="{{ route('accounting.invoices.pay', $invoice) }}">
                    @csrf

                    {{-- المتبقي hint --}}
                    <div class="ac-pay-remaining">
                        <span>المتبقي</span>
                        <strong class="ac-text-danger">{{ number_format($invoice->remaining_amount, 2) }}</strong>
                    </div>

                    {{-- المبلغ --}}
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="payment_amount">المبلغ</label>
                        <div class="ac-pay-amount-row">
                            <input type="number" step="0.01" min="0.01"
                                   max="{{ $invoice->remaining_amount }}"
                                   id="payment_amount" name="payment_amount"
                                   class="ac-control ac-control--num {{ $errors->has('payment_amount') ? 'ac-control--error' : '' }}"
                                   placeholder="0.00" required>
                            <button type="button" class="ac-btn ac-btn--secondary ac-btn--sm"
                                    onclick="document.getElementById('payment_amount').value='{{ $invoice->remaining_amount }}'">
                                الكامل
                            </button>
                        </div>
                        @error('payment_amount') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- طريقة الدفع --}}
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="payment_method">طريقة الدفع</label>
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
                    </div>

                    {{-- التاريخ --}}
                    <div class="ac-form-group">
                        <label class="ac-label" for="payment_date">تاريخ السداد</label>
                        <input type="date" id="payment_date" name="payment_date"
                               class="ac-control"
                               value="{{ old('payment_date', today()->toDateString()) }}">
                    </div>

                    {{-- ملاحظات --}}
                    <div class="ac-form-group">
                        <label class="ac-label" for="payment_notes">ملاحظات</label>
                        <input type="text" id="payment_notes" name="payment_notes"
                               class="ac-control" placeholder="اختياري">
                    </div>

                    <button type="submit" class="ac-btn ac-btn--success ac-btn--full"
                            onclick="return confirm('تأكيد تسجيل الدفعة؟')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        تسجيل السداد
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endcan
    @endif

    {{-- ── سجل الدفعات ── --}}
    @if($payments->isNotEmpty())
    <div class="ac-inv-pay-panel ac-no-print">
        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">سجل السداد ({{ $payments->count() }})</p>
                <div class="ac-pay-history">
                    @foreach($payments as $pay)
                    <div class="ac-pay-history-item">
                        <div class="ac-pay-history-item__method">
                            <span class="ac-pay-method-icon">{{ \App\Modules\Accounting\Models\Payment::methodIcon($pay->payment_method) }}</span>
                            <span class="ac-pay-method-name">{{ \App\Modules\Accounting\Models\Payment::methodLabel($pay->payment_method) }}</span>
                        </div>
                        <div class="ac-pay-history-item__meta">
                            <span class="ac-pay-history-item__date">{{ $pay->payment_date->format('Y/m/d') }}</span>
                            @if($pay->notes)
                                <span class="ac-pay-history-item__notes">{{ $pay->notes }}</span>
                            @endif
                        </div>
                        <div class="ac-pay-history-item__amount">
                            +{{ number_format($pay->amount, 2) }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── إشعارات دائنة ── --}}
    @if($invoice->creditNotes->isNotEmpty())
    <div class="ac-inv-pay-panel ac-no-print">
        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">الإشعارات الدائنة ({{ $invoice->creditNotes->count() }})</p>
                <div class="ac-pay-history">
                    @foreach($invoice->creditNotes as $cn)
                    <div class="ac-pay-history-item">
                        <div class="ac-pay-history-item__method">
                            <span class="ac-pay-method-icon">📋</span>
                            <span class="ac-pay-method-name">
                                <a href="{{ route('accounting.credit-notes.show', $cn) }}" class="ac-link">
                                    {{ $cn->credit_note_number }}
                                </a>
                            </span>
                        </div>
                        <div class="ac-pay-history-item__meta">
                            <span class="ac-pay-history-item__date">{{ $cn->issue_date->format('Y/m/d') }}</span>
                            @if($cn->reason)
                                <span class="ac-pay-history-item__notes">{{ $cn->reason }}</span>
                            @endif
                        </div>
                        <div class="ac-pay-history-item__amount ac-text-danger">
                            −{{ number_format($cn->total, 2) }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

</div>{{-- .ac-inv-layout --}}

@endsection

@push('scripts')
{{-- QRCode.js from jsDelivr (lightweight, no server dependency) --}}
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('invoice-qr');
    if (!el || typeof QRCode === 'undefined') return;

    const data = @json($invoice->qrData());

    QRCode.toCanvas(document.createElement('canvas'), data, {
        width:  120,
        margin: 1,
        color:  { dark: '#1e293b', light: '#ffffff' }
    }, function (err, canvas) {
        if (!err) el.appendChild(canvas);
    });
});
</script>
@endpush

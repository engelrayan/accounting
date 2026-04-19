@extends('accounting._layout')

@section('title', $customer->name)

@section('topbar-actions')
<div style="display:flex;gap:8px;align-items:center;">
    @if($balance > 0)
        @can('can-write')
        <button type="button" class="ac-btn ac-btn--danger" data-modal="settle-modal">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            تسوية الحساب
        </button>
        @endcan
    @endif
    <a href="{{ route('accounting.reports.customer-statement', $customer->id) }}"
       class="ac-btn ac-btn--secondary ac-btn--sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14,2 14,8 20,8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        كشف حساب
    </a>
    <a href="{{ route('accounting.customers.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>
@endsection

@section('content')

@if(session('success'))
    <div class="ac-alert ac-alert--success" data-dismiss="alert">{{ session('success') }}</div>
@endif

{{-- ── Profile Header ───────────────────────────────────────────────────────── --}}
<div class="ac-cust-profile-header">
    <div class="ac-cust-avatar ac-cust-avatar--lg">{{ $customer->initial() }}</div>
    <div class="ac-cust-profile-info">
        <h1 class="ac-cust-profile-name">{{ $customer->name }}</h1>
        <div class="ac-cust-profile-meta">
            @if($customer->phone)
                <span class="ac-cust-profile-meta__item">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.67A2 2 0 012 1h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
                    </svg>
                    {{ $customer->phone }}
                </span>
            @endif
            @if($customer->email)
                <span class="ac-cust-profile-meta__item">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    {{ $customer->email }}
                </span>
            @endif
            @if($customer->address)
                <span class="ac-cust-profile-meta__item">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    {{ $customer->address }}
                </span>
            @endif
        </div>
    </div>
</div>

{{-- ── Balance Summary Cards ─────────────────────────────────────────────────── --}}
<div class="ac-dash-grid">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي الفواتير</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($totalInvoiced, 2) }}</div>
        <div class="ac-dash-card__footer">{{ $customer->invoices->count() }} فاتورة</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي المحصَّل</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-success">{{ number_format($totalPaid, 2) }}</div>
        <div class="ac-dash-card__footer">{{ $customer->payments->count() }} دفعة مستلمة</div>
    </div>

    <div class="ac-dash-card {{ $balance > 0 ? 'ac-dash-card--alert' : '' }}">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">الرصيد المستحق</span>
            <div class="ac-dash-card__icon {{ $balance > 0 ? 'ac-dash-card__icon--red' : 'ac-dash-card__icon--green' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $balance > 0 ? 'ac-text-danger' : 'ac-text-success' }}">
            {{ number_format($balance, 2) }}
        </div>
        <div class="ac-dash-card__footer">
            @if($balance > 0)
                <span class="ac-text-danger">مستحق على العميل</span>
            @elseif($balance < 0)
                رصيد دائن للعميل
            @else
                الحساب مسوَّى بالكامل
            @endif
        </div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">حالة الفواتير</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 11l3 3L22 4"/>
                    <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $unpaidInvoices->count() }}</div>
        <div class="ac-dash-card__footer">
            غير مسدَّدة
            @if($overdueInvoices->count() > 0)
                · <span class="ac-text-danger">{{ $overdueInvoices->count() }} متأخرة</span>
            @endif
        </div>
    </div>

</div>

{{-- ── Outstanding Balance Banner ────────────────────────────────────────────── --}}
@if($balance > 0)
<div class="ac-balance-banner">
    <div class="ac-balance-banner__icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
    </div>
    <div class="ac-balance-banner__body">
        <strong>يوجد رصيد مستحق</strong>
        <span>على هذا العميل مبلغ <strong class="ac-text-danger">{{ number_format($balance, 2) }}</strong> لم يُسدَّد بعد.</span>
    </div>
    @can('can-write')
    <a href="#pay-panel" class="ac-btn ac-btn--danger ac-btn--sm">تسجيل دفعة</a>
    @endcan
</div>
@endif

{{-- ── Overdue Warning ────────────────────────────────────────────────────────── --}}
@if($overdueInvoices->count() > 0)
<div class="ac-overdue-banner">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        <line x1="12" y1="9" x2="12" y2="13"/>
        <line x1="12" y1="17" x2="12.01" y2="17"/>
    </svg>
    <span>
        {{ $overdueInvoices->count() }} {{ $overdueInvoices->count() === 1 ? 'فاتورة متأخرة' : 'فواتير متأخرة' }}
        — تجاوزت تاريخ الاستحقاق ولم تُسدَّد.
    </span>
</div>
@endif

@can('can-write')
{{-- ── Action Panels ─────────────────────────────────────────────────────────── --}}
<div class="ac-partner-actions" id="pay-panel">

    {{-- New Invoice --}}
    <div class="ac-card">
        <div class="ac-card__body">
            <p class="ac-section-label">إصدار فاتورة جديدة</p>
            <p class="ac-text-muted" style="font-size:.85rem;margin-bottom:12px;">
                ستُنشئ فاتورة رسمية مع بنود تفصيلية وكود QR للطباعة.
            </p>
            <a href="{{ route('accounting.invoices.create', ['customer_id' => $customer->id]) }}"
               class="ac-btn ac-btn--primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                إنشاء فاتورة
            </a>
        </div>
    </div>

    {{-- Record Payment --}}
    <div class="ac-card">
        <div class="ac-card__body">
            <p class="ac-section-label">تسجيل دفعة</p>
            <form method="POST" action="{{ route('accounting.customers.payments.store', $customer) }}">
                @csrf

                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="pay_amount">المبلغ</label>
                        <input type="number" step="0.01" min="0.01"
                               id="pay_amount" name="amount"
                               class="ac-control ac-control--num {{ $errors->has('amount') ? 'ac-control--error' : '' }}"
                               placeholder="0.00" value="{{ old('amount') }}" required>
                        @error('amount') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="pay_date">تاريخ الدفع</label>
                        <input type="date" id="pay_date" name="payment_date"
                               value="{{ old('payment_date', today()->toDateString()) }}"
                               class="ac-control {{ $errors->has('payment_date') ? 'ac-control--error' : '' }}" required>
                        @error('payment_date') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="ac-form-group">
                    <label class="ac-label ac-label--required" for="pay_invoice">الفاتورة</label>
                    @if($pendingInvoices->isNotEmpty())
                    <select id="pay_invoice" name="invoice_id"
                            class="ac-select {{ $errors->has('invoice_id') ? 'ac-select--error' : '' }}" required>
                        <option value="">— اختر الفاتورة —</option>
                        @foreach($pendingInvoices->sortBy('issue_date') as $inv)
                            <option value="{{ $inv->id }}" {{ old('invoice_id') == $inv->id ? 'selected' : '' }}>
                                {{ $inv->invoice_number }}
                                ({{ number_format($inv->remaining(), 2) }} متبقي){{ $inv->isOverdue() ? ' — متأخرة' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('invoice_id') <span class="ac-field-error">{{ $message }}</span> @enderror
                    @else
                    <p class="ac-text-muted" style="font-size:.85rem;">لا توجد فواتير مستحقة لهذا العميل.</p>
                    @endif
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
                    <input type="text" id="pay_notes" name="notes"
                           value="{{ old('notes') }}"
                           class="ac-control" placeholder="اختياري">
                </div>

                @error('payment') <div class="ac-alert ac-alert--error ac-mt-2">{{ $message }}</div> @enderror

                <button type="submit" class="ac-btn ac-btn--success ac-btn--full"
                        @if($pendingInvoices->isEmpty()) disabled title="لا توجد فواتير مستحقة" @endif>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    تسجيل الدفعة
                </button>
            </form>
        </div>
    </div>

</div>
@endcan

{{-- ── Unpaid Invoices (prominent section) ─────────────────────────────────── --}}
@if($unpaidInvoices->isNotEmpty())
<h2 class="ac-section-title ac-mt-4">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
    </svg>
    الفواتير غير المسدَّدة
    <span class="ac-count-pill ac-count-pill--danger">{{ $unpaidInvoices->count() }}</span>
</h2>
<div class="ac-card ac-card--bordered-danger">
    <div class="ac-card__body" style="padding:0;">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>الوصف</th>
                    <th>تاريخ الإصدار</th>
                    <th>تاريخ الاستحقاق</th>
                    <th class="ac-table__num">الإجمالي</th>
                    <th class="ac-table__num">المدفوع</th>
                    <th class="ac-table__num">المتبقي</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unpaidInvoices->sortBy(fn($i) => $i->isOverdue() ? 0 : 1) as $invoice)
                <tr class="{{ $invoice->isOverdue() ? 'ac-row--overdue' : 'ac-row--unpaid' }}">
                    <td class="ac-text-mono ac-font-bold">{{ $invoice->invoice_number }}</td>
                    <td class="ac-table__muted">{{ $invoice->notes ?: '—' }}</td>
                    <td class="ac-table__muted">{{ $invoice->issue_date->format('Y-m-d') }}</td>
                    <td>
                        @if($invoice->due_date)
                            <span class="{{ $invoice->isOverdue() ? 'ac-text-danger ac-font-bold' : 'ac-table__muted' }}">
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
                    <td class="ac-table__num ac-text-danger ac-font-bold">
                        {{ number_format($invoice->remaining(), 2) }}
                    </td>
                    <td>
                        <div class="ac-cust-inv-status">
                            <span class="ac-badge ac-badge--{{ $invoice->statusMod() }}">
                                {{ $invoice->statusLabel() }}
                            </span>
                            @if($invoice->amount > 0 && $invoice->totalPaid() > 0)
                                <div class="ac-progress-bar ac-progress-bar--xs">
                                    <div class="ac-progress-fill" data-pct="{{ $invoice->paidPct() }}"></div>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="ac-table__foot-total">
                    <td colspan="4" class="ac-font-bold">مجموع غير المسدَّد</td>
                    <td class="ac-table__num ac-font-bold">
                        {{ number_format($unpaidInvoices->sum(fn($i) => (float) $i->amount), 2) }}
                    </td>
                    <td class="ac-table__num ac-font-bold ac-text-success">
                        {{ number_format($unpaidInvoices->sum(fn($i) => $i->totalPaid()), 2) }}
                    </td>
                    <td class="ac-table__num ac-font-bold ac-text-danger">
                        {{ number_format($unpaidInvoices->sum(fn($i) => $i->remaining()), 2) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- ── All Invoices ─────────────────────────────────────────────────────────── --}}
<h2 class="ac-section-title ac-mt-4">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
        <polyline points="14,2 14,8 20,8"/>
    </svg>
    جميع الفواتير
    <span class="ac-count-pill">{{ $customer->invoices->count() }}</span>
</h2>

@if($customer->invoices->isEmpty())
    <div class="ac-empty-state ac-empty-state--sm ac-text-muted">لم يتم إصدار أي فواتير بعد.</div>
@else
<div class="ac-card">
    <div class="ac-card__body" style="padding:0;">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>الوصف</th>
                    <th>تاريخ الإصدار</th>
                    <th>تاريخ الاستحقاق</th>
                    <th class="ac-table__num">الإجمالي</th>
                    <th class="ac-table__num">المدفوع</th>
                    <th class="ac-table__num">المتبقي</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customer->invoices as $invoice)
                <tr class="{{ $invoice->isOverdue() ? 'ac-row--overdue' : ($invoice->isUnpaid() ? 'ac-row--unpaid' : '') }}">
                    <td class="ac-text-mono ac-font-bold">{{ $invoice->invoice_number }}</td>
                    <td class="ac-table__muted">{{ $invoice->notes ?: '—' }}</td>
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
                            <span class="ac-badge ac-badge--{{ $invoice->statusMod() }}">
                                {{ $invoice->statusLabel() }}
                            </span>
                            @if($invoice->status !== 'paid' && (float) $invoice->amount > 0 && $invoice->totalPaid() > 0)
                                <div class="ac-progress-bar ac-progress-bar--xs">
                                    <div class="ac-progress-fill" data-pct="{{ $invoice->paidPct() }}"></div>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="ac-table__foot-total">
                    <td colspan="4" class="ac-font-bold">الإجمالي</td>
                    <td class="ac-table__num ac-font-bold">{{ number_format($totalInvoiced, 2) }}</td>
                    <td class="ac-table__num ac-font-bold ac-text-success">{{ number_format($totalPaid, 2) }}</td>
                    <td class="ac-table__num ac-font-bold {{ $balance > 0 ? 'ac-text-danger' : '' }}">
                        {{ number_format(max(0, $totalInvoiced - $totalPaid), 2) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- ── Payment History ──────────────────────────────────────────────────────── --}}
<h2 class="ac-section-title ac-mt-4">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
        <line x1="1" y1="10" x2="23" y2="10"/>
    </svg>
    سجل الدفعات
    <span class="ac-count-pill ac-count-pill--green">{{ $customer->payments->count() }}</span>
</h2>

@if($customer->payments->isEmpty())
    <div class="ac-empty-state ac-empty-state--sm ac-text-muted">لم يتم تسجيل أي دفعات بعد.</div>
@else
<div class="ac-card">
    <div class="ac-card__body" style="padding:0;">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>تاريخ الدفع</th>
                    <th>طريقة الدفع</th>
                    <th class="ac-table__num">المبلغ</th>
                    <th>مرتبطة بفاتورة</th>
                    <th>ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customer->payments as $payment)
                <tr>
                    <td class="ac-table__muted">{{ \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') }}</td>
                    <td>
                        <span class="ac-pay-method-pill ac-pay-method-pill--{{ $payment->payment_method }}">
                            {{ \App\Modules\Accounting\Models\Payment::methodIcon($payment->payment_method) }}
                            {{ \App\Modules\Accounting\Models\Payment::methodLabel($payment->payment_method) }}
                        </span>
                    </td>
                    <td class="ac-table__num ac-font-bold ac-text-success">
                        +{{ number_format($payment->amount, 2) }}
                    </td>
                    <td>
                        @if($payment->invoice)
                            <span class="ac-text-mono">{{ $payment->invoice->invoice_number }}</span>
                        @else
                            <span class="ac-table__muted">دفعة عامة</span>
                        @endif
                    </td>
                    <td class="ac-table__muted">{{ $payment->notes ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="ac-table__foot-total">
                    <td colspan="2" class="ac-font-bold">إجمالي المحصَّل</td>
                    <td class="ac-table__num ac-font-bold ac-text-success">
                        +{{ number_format($totalPaid, 2) }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

@endsection

{{-- ════════════════════════════════════════════════════════════════════════
     SETTLEMENT CONFIRMATION MODAL
     ═══════════════════════════════════════════════════════════════════════ --}}
@can('can-write')
@if($balance > 0)
<div id="settle-modal" class="ac-modal" role="dialog" aria-modal="true" aria-labelledby="settle-modal-title" hidden>
    <div class="ac-modal__backdrop" data-modal-close="settle-modal"></div>
    <div class="ac-modal__dialog">

        {{-- Header --}}
        <div class="ac-modal__header">
            <div class="ac-modal__icon ac-modal__icon--warning">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div>
                <h2 class="ac-modal__title" id="settle-modal-title">تسوية الحساب</h2>
                <p class="ac-modal__subtitle">{{ $customer->name }}</p>
            </div>
            <button type="button" class="ac-modal__close" data-modal-close="settle-modal" aria-label="إغلاق">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="ac-modal__body">
            <p class="ac-modal__message">
                هل أنت متأكد من تسوية حساب هذا العميل؟<br>
                سيتم اعتبار جميع الفواتير المستحقة مدفوعةً وتسجيل دفعة تلقائية لكل منها.
            </p>

            <div class="ac-modal__summary">
                <div class="ac-modal__summary-row">
                    <span>عدد الفواتير المستحقة</span>
                    <strong>{{ $unpaidInvoices->count() }}</strong>
                </div>
                <div class="ac-modal__summary-row">
                    <span>إجمالي المبلغ</span>
                    <strong class="ac-text-danger">
                        {{ number_format($unpaidInvoices->sum(fn($i) => $i->remaining()), 2) }}
                    </strong>
                </div>
                <div class="ac-modal__summary-row">
                    <span>تاريخ التسوية</span>
                    <strong>{{ today()->format('Y-m-d') }}</strong>
                </div>
            </div>

            <p class="ac-modal__warning">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                لا يمكن التراجع عن هذه العملية بعد التأكيد.
            </p>
        </div>

        {{-- Footer --}}
        <div class="ac-modal__footer">
            <button type="button" class="ac-btn ac-btn--secondary" data-modal-close="settle-modal">
                إلغاء
            </button>
            <form method="POST" action="{{ route('accounting.customers.settle', $customer) }}"
                  style="margin:0;display:flex;align-items:center;gap:8px;">
                @csrf
                <select name="settlement_type" class="ac-select" style="min-width:140px;" required>
                    <option value="discount">خصم تجاري</option>
                    <option value="bad_debt">إعدام دين</option>
                </select>
                <button type="submit" class="ac-btn ac-btn--danger">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    تأكيد التسوية
                </button>
            </form>
        </div>

    </div>
</div>
@endif
@endcan

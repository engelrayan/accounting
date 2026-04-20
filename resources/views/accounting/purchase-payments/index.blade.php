@extends('accounting._layout')

@section('title', 'مدفوعات الموردين')

@php
    use App\Modules\Accounting\Models\PurchasePayment;

    $hasFilters = filled($method) || filled($from) || filled($to);
    $pageTotal = $payments->sum('amount');
    $methodOptions = [
        'cash' => 'نقداً',
        'bank' => 'تحويل بنكي',
        'wallet' => 'محفظة',
        'instapay' => 'إنستاباي',
        'cheque' => 'شيك',
        'card' => 'بطاقة',
        'other' => 'أخرى',
    ];
@endphp

@section('content')
<section class="ac-pp-page">
    <div class="ac-pp-hero">
        <div class="ac-pp-hero__content">
            <span class="ac-pp-hero__eyebrow">الموردون</span>
            <h1>مدفوعات الموردين</h1>
            <p>تابع كل المبالغ الخارجة للموردين مع الفواتير وطريقة الدفع في مكان واحد.</p>
        </div>

        <div class="ac-pp-hero__summary">
            <span>إجمالي الفترة</span>
            <strong>{{ number_format($totalAmount, 2) }}</strong>
            <small>{{ $payments->total() }} عملية دفع</small>
        </div>
    </div>

    <div class="ac-pp-stats">
        <article class="ac-pp-stat ac-pp-stat--danger">
            <span class="ac-pp-stat__label">إجمالي المدفوع</span>
            <strong>{{ number_format($totalAmount, 2) }}</strong>
            <small>{{ $hasFilters ? 'حسب التصفية الحالية' : 'كل المدفوعات المسجلة' }}</small>
        </article>

        <article class="ac-pp-stat">
            <span class="ac-pp-stat__label">عدد العمليات</span>
            <strong>{{ number_format($payments->total()) }}</strong>
            <small>دفعات مورّدين</small>
        </article>

        <article class="ac-pp-stat">
            <span class="ac-pp-stat__label">إجمالي الصفحة</span>
            <strong>{{ number_format($pageTotal, 2) }}</strong>
            <small>{{ $payments->count() }} سجل ظاهر الآن</small>
        </article>
    </div>

    <form method="GET" action="{{ route('accounting.purchase-payments.index') }}" class="ac-pp-filter">
        <div class="ac-pp-filter__head">
            <div>
                <h2>تصفية المدفوعات</h2>
                <p>اختار فترة أو طريقة دفع للوصول للدفعات المطلوبة بسرعة.</p>
            </div>

            @if($hasFilters)
                <a href="{{ route('accounting.purchase-payments.index') }}" class="ac-btn ac-btn--ghost">
                    إزالة التصفية
                </a>
            @endif
        </div>

        <div class="ac-pp-filter__grid">
            <div class="ac-form-group">
                <label class="ac-label" for="filter_from">من تاريخ</label>
                <input type="date" id="filter_from" name="from" class="ac-control" value="{{ $from }}">
            </div>

            <div class="ac-form-group">
                <label class="ac-label" for="filter_to">إلى تاريخ</label>
                <input type="date" id="filter_to" name="to" class="ac-control" value="{{ $to }}">
            </div>

            <div class="ac-form-group">
                <label class="ac-label" for="filter_method">طريقة الدفع</label>
                <select id="filter_method" name="method" class="ac-select">
                    <option value="">كل الطرق</option>
                    @foreach($methodOptions as $value => $label)
                        <option value="{{ $value }}" {{ $method === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="ac-pp-filter__actions">
                <button type="submit" class="ac-btn ac-btn--primary">تطبيق التصفية</button>
                <a href="{{ route('accounting.purchase-payments.index') }}" class="ac-btn ac-btn--secondary">إعادة ضبط</a>
            </div>
        </div>
    </form>

    @if($methodTotals->isNotEmpty())
        <div class="ac-pp-methods" aria-label="إجمالي المدفوعات حسب الطريقة">
            @foreach($methodTotals as $paymentMethod => $total)
                <article class="ac-pp-method">
                    <span>{{ PurchasePayment::methodLabel($paymentMethod) }}</span>
                    <strong>{{ number_format($total->total, 2) }}</strong>
                    <small>{{ $total->cnt }} دفعة</small>
                </article>
            @endforeach
        </div>
    @endif

    @if($payments->isEmpty())
        <div class="ac-pp-empty">
            <div class="ac-pp-empty__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <path d="M2 10h20"/>
                    <path d="M7 15h5"/>
                </svg>
            </div>
            <h2>{{ $hasFilters ? 'لا توجد دفعات بهذه التصفية' : 'لا توجد مدفوعات بعد' }}</h2>
            <p>
                {{ $hasFilters
                    ? 'جرّب تغيير الفترة أو طريقة الدفع، أو أزل التصفية لعرض كل المدفوعات.'
                    : 'سجّل دفعة من صفحة المورد أو من فاتورة الشراء، وستظهر هنا تلقائياً.' }}
            </p>
            @if($hasFilters)
                <a href="{{ route('accounting.purchase-payments.index') }}" class="ac-btn ac-btn--primary">
                    عرض كل المدفوعات
                </a>
            @else
                <a href="{{ route('accounting.vendors.index') }}" class="ac-btn ac-btn--primary">
                    فتح الموردين
                </a>
            @endif
        </div>
    @else
        <div class="ac-card ac-pp-table-card">
            <div class="ac-card__header">
                <div>
                    <h2 class="ac-card__title">سجل المدفوعات</h2>
                    <p class="ac-card__subtitle">آخر الدفعات مرتبة من الأحدث للأقدم.</p>
                </div>
            </div>

            <div class="ac-table-wrap">
                <table class="ac-table ac-pp-table">
                    <thead>
                        <tr>
                            <th>تاريخ الدفع</th>
                            <th>المورد</th>
                            <th>الفاتورة</th>
                            <th>طريقة الدفع</th>
                            <th class="ac-table__num">المبلغ</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td class="ac-table__muted">{{ $payment->payment_date?->format('Y-m-d') }}</td>
                                <td>
                                    @if($payment->vendor)
                                        <a href="{{ route('accounting.vendors.show', $payment->vendor_id) }}" class="ac-link">
                                            {{ $payment->vendor->name }}
                                        </a>
                                    @else
                                        <span class="ac-table__muted">غير محدد</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->purchaseInvoice)
                                        <a href="{{ route('accounting.purchase-invoices.show', $payment->purchase_invoice_id) }}" class="ac-code-tag">
                                            {{ $payment->purchaseInvoice->invoice_number }}
                                        </a>
                                    @else
                                        <span class="ac-table__muted">بدون فاتورة</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="ac-pay-method-pill ac-pay-method-pill--{{ $payment->payment_method }}">
                                        {{ PurchasePayment::methodLabel($payment->payment_method) }}
                                    </span>
                                </td>
                                <td class="ac-table__num ac-pp-amount">
                                    -{{ number_format($payment->amount, 2) }}
                                </td>
                                <td class="ac-table__muted">{{ $payment->notes ?: 'لا توجد ملاحظات' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4">إجمالي الصفحة</td>
                            <td class="ac-table__num ac-pp-amount">-{{ number_format($pageTotal, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($payments->hasPages())
            <div class="ac-pagination">{{ $payments->links() }}</div>
        @endif
    @endif
</section>
@endsection

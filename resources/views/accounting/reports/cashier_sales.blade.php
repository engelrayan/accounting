@extends('accounting._layout')

@section('title', 'مبيعات الكاشير')

@php
    $fmt = fn (float $value) => number_format($value, 2);
@endphp

@section('topbar-actions')
    <div class="ac-topbar-actions">
        <a href="{{ route('accounting.pos.create') }}" class="ac-btn ac-btn--primary ac-btn--sm">نقطة البيع</a>
        <a href="{{ route('accounting.reports.income-expense') }}" class="ac-btn ac-btn--secondary ac-btn--sm">الدخل والمصروف</a>
    </div>
@endsection

@section('content')

<div class="ac-report-hero ac-report-hero--income-expense">
    <div class="ac-report-hero__content">
        <span class="ac-report-hero__eyebrow">متابعة تشغيلية</span>
        <h2 class="ac-report-hero__title">صورة مباشرة لأداء الكاشير</h2>
        <p class="ac-report-hero__text">
            راقب عدد العمليات، إجمالي الخصومات، التحصيل، والرصيد المفتوح لكل كاشير خلال الفترة المحددة.
        </p>
    </div>
    <div class="ac-report-hero__meta">
        <span class="ac-report-hero__badge">POS</span>
        <strong>{{ $from }} — {{ $to }}</strong>
        <span>{{ $cashier_id ? 'كاشير محدد' : 'كل الكاشير' }}</span>
    </div>
</div>

<div class="ac-card ac-report-filter-card">
    <div class="ac-card__body">
        <form method="GET" action="{{ route('accounting.reports.cashier-sales') }}" class="ac-report-filter">
            <div class="ac-form-row">
                <div class="ac-form-group">
                    <label class="ac-label" for="from">من تاريخ</label>
                    <input id="from" name="from" type="date" class="ac-control" value="{{ $from }}">
                </div>
                <div class="ac-form-group">
                    <label class="ac-label" for="to">إلى تاريخ</label>
                    <input id="to" name="to" type="date" class="ac-control" value="{{ $to }}">
                </div>
                <div class="ac-form-group">
                    <label class="ac-label" for="cashier_id">الكاشير</label>
                    <select id="cashier_id" name="cashier_id" class="ac-select">
                        <option value="">كل الكاشير</option>
                        @foreach($cashiers as $cashier)
                            <option value="{{ $cashier->id }}" {{ (string) $cashier_id === (string) $cashier->id ? 'selected' : '' }}>
                                {{ $cashier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="ac-form-group ac-form-group--end">
                    <button type="submit" class="ac-btn ac-btn--primary">تطبيق</button>
                    <a href="{{ route('accounting.reports.cashier-sales') }}" class="ac-btn ac-btn--secondary">إعادة الضبط</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="ac-dash-grid ac-dash-grid--4">
    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">عدد العمليات</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2h12"/>
                    <path d="M7 6h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($summary['count']) }}</div>
        <div class="ac-dash-card__footer">إجمالي فواتير POS خلال الفترة</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي المبيعات</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="19" x2="12" y2="5"/>
                    <polyline points="5,12 12,5 19,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-success">{{ $fmt($summary['gross']) }}</div>
        <div class="ac-dash-card__footer">قبل الخصم والضريبة</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">الخصومات</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 14L15 8"/>
                    <circle cx="9.5" cy="8.5" r="1.5"/>
                    <circle cx="14.5" cy="13.5" r="1.5"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $fmt($summary['discounts']) }}</div>
        <div class="ac-dash-card__footer">خصم مباشر من شاشة البيع</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">المحصّل</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14"/>
                    <path d="M12 5l7 7-7 7"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-success">{{ $fmt($summary['paid']) }}</div>
        <div class="ac-dash-card__footer">
            متبقي <span class="{{ $summary['remaining'] > 0 ? 'ac-text-danger' : 'ac-text-success' }}">{{ $fmt($summary['remaining']) }}</span>
        </div>
    </div>
</div>

<div class="ac-report-kpi-grid">
    <section class="ac-report-panel">
        <div class="ac-report-panel__header">
            <div>
                <h3>الأداء حسب الكاشير</h3>
                <p>من يساعد الفريق أكثر في إنهاء العمليات وتحقيق صافي أعلى.</p>
            </div>
            <span class="ac-report-panel__badge">{{ $by_cashier->count() }} كاشير</span>
        </div>

        @if($by_cashier->isEmpty())
            <div class="ac-empty-state ac-empty-state--sm">
                لا توجد عمليات POS في هذه الفترة.
            </div>
        @else
            <div class="ac-table-wrap">
                <table class="ac-table">
                    <thead>
                        <tr>
                            <th>الكاشير</th>
                            <th class="ac-col-num">عدد العمليات</th>
                            <th class="ac-col-num">الإجمالي</th>
                            <th class="ac-col-num">الخصم</th>
                            <th class="ac-col-num">الصافي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($by_cashier as $row)
                            <tr>
                                <td>{{ $row['cashier'] }}</td>
                                <td class="ac-col-num">{{ number_format($row['count']) }}</td>
                                <td class="ac-col-num">{{ $fmt($row['gross']) }}</td>
                                <td class="ac-col-num">{{ $fmt($row['discounts']) }}</td>
                                <td class="ac-col-num ac-text-success">{{ $fmt($row['net']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="ac-report-panel">
        <div class="ac-report-panel__header">
            <div>
                <h3>توزيع طرق الدفع</h3>
                <p>يعرض كيف يفضّل العملاء الدفع داخل نقطة البيع.</p>
            </div>
            <span class="ac-report-panel__badge">{{ $payment_mix->count() }} طريقة</span>
        </div>

        @if($payment_mix->isEmpty())
            <div class="ac-empty-state ac-empty-state--sm">
                لا توجد مدفوعات مسجلة بعد.
            </div>
        @else
            <div class="ac-report-mix-list">
                @foreach($payment_mix as $mix)
                    <div class="ac-report-mix-card">
                        <div class="ac-report-mix-card__head">
                            <strong>{{ $mix['method'] }}</strong>
                            <span>{{ number_format($mix['count']) }} عملية</span>
                        </div>
                        <div class="ac-report-mix-card__amount">{{ $fmt($mix['total']) }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>

<section class="ac-report-panel">
    <div class="ac-report-panel__header">
        <div>
            <h3>تفاصيل فواتير الكاشير</h3>
            <p>آخر عمليات POS خلال الفترة مع الكاشير والعميل وحالة التحصيل.</p>
        </div>
        <span class="ac-report-panel__badge">{{ $sales->count() }} فاتورة</span>
    </div>

    @if($sales->isEmpty())
        <div class="ac-empty-state ac-empty-state--sm">
            لا توجد فواتير مبيعات كاشير لعرضها الآن.
        </div>
    @else
        <div class="ac-table-wrap">
            <table class="ac-table">
                <thead>
                    <tr>
                        <th>الفاتورة</th>
                        <th>الكاشير</th>
                        <th>العميل</th>
                        <th>الدفع</th>
                        <th class="ac-col-num">الصافي</th>
                        <th class="ac-col-num">المحصّل</th>
                        <th class="ac-col-num">المتبقي</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales as $sale)
                        <tr>
                            <td>
                                <div class="ac-report-stack">
                                    <a href="{{ route('accounting.invoices.show', $sale) }}" class="ac-link">{{ $sale->invoice_number }}</a>
                                    @if((float) $sale->discount_amount > 0)
                                        <span class="ac-report-inline-badge">خصم {{ $fmt((float) $sale->discount_amount) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $sale->creator?->name ?? 'غير محدد' }}</td>
                            <td>{{ $sale->customer?->name ?? 'عميل نقدي' }}</td>
                            <td>{{ $sale->paymentMethodLabel() }}</td>
                            <td class="ac-col-num">{{ $fmt((float) $sale->amount) }}</td>
                            <td class="ac-col-num ac-text-success">{{ $fmt((float) $sale->paid_amount) }}</td>
                            <td class="ac-col-num {{ (float) $sale->remaining_amount > 0 ? 'ac-text-danger' : 'ac-text-success' }}">
                                {{ $fmt((float) $sale->remaining_amount) }}
                            </td>
                            <td>{{ $sale->issue_date?->format('Y/m/d') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

@endsection

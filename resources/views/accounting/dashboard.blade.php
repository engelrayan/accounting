@extends('accounting._layout')

@section('title', 'لوحة التحكم')

@php
    $fmt = fn ($value, $decimals = 2) => number_format((float) $value, $decimals);
    $fmtCompact = function ($value): string {
        $value = (float) $value;

        if (abs($value) >= 1000000) {
            return number_format($value / 1000000, 1) . 'M';
        }

        if (abs($value) >= 1000) {
            return number_format($value / 1000, 1) . 'K';
        }

        return number_format($value, 2);
    };

    $months = [
        '01' => 'يناير',
        '02' => 'فبراير',
        '03' => 'مارس',
        '04' => 'أبريل',
        '05' => 'مايو',
        '06' => 'يونيو',
        '07' => 'يوليو',
        '08' => 'أغسطس',
        '09' => 'سبتمبر',
        '10' => 'أكتوبر',
        '11' => 'نوفمبر',
        '12' => 'ديسمبر',
    ];

    $monthName = $months[now()->format('m')] ?? now()->format('m');
    $profitState = $profitMonth >= 0 ? 'positive' : 'negative';
    $expenseRatio = $revenueMonth > 0 ? min(100, round(($expenseMonth / max($revenueMonth, 1)) * 100)) : 0;
    $cashTotal = max(abs($totalCash), 1);
    $cashShare = round((abs($cashBalance) / $cashTotal) * 100);
    $bankShare = 100 - $cashShare;
    $dashboardJson = json_encode([
        'chartData' => $chartData,
        'breakdownData' => $breakdownData,
        'weeklyChartData' => $weeklyChartData,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp

@section('topbar-actions')
    <div class="ac-topbar-actions">
        @can('can-write')
            <a href="{{ route('accounting.transactions.create') }}" class="ac-btn ac-btn--secondary ac-btn--sm">+ معاملة جديدة</a>
            <a href="{{ route('accounting.invoices.create') }}" class="ac-btn ac-btn--primary ac-btn--sm">+ فاتورة جديدة</a>
        @endcan
    </div>
@endsection

@section('content')
<div class="ac-db">
    <section class="ac-db-hero">
        <div class="ac-db-hero__content">
            <span class="ac-db-eyebrow">{{ now()->day }} {{ $monthName }} {{ now()->year }}</span>
            <h2>مرحبًا، {{ auth()->user()->name }}</h2>
            <p>ملخص واضح لأداء شركتك هذا الشهر: دخل، مصروفات، نقدية، ومتابعة سريعة للمستحقات.</p>
        </div>

        <div class="ac-db-hero__panel">
            <span>صافي ربح الشهر</span>
            <strong class="ac-db-amount ac-db-amount--{{ $profitState }}">{{ $fmt($profitMonth) }}</strong>
            <div class="ac-db-ratio">
                <div>
                    <b>{{ $fmt($revenueMonth) }}</b>
                    <span>إيرادات</span>
                </div>
                <div>
                    <b>{{ $fmt($expenseMonth) }}</b>
                    <span>مصروفات</span>
                </div>
            </div>
            <div class="ac-db-progress" data-progress="{{ $expenseRatio }}">
                <span></span>
            </div>
            <small>المصروفات تمثل {{ $expenseRatio }}% من إيرادات الشهر</small>
        </div>
    </section>

    <section class="ac-db-kpi-grid">
        <article class="ac-db-kpi ac-db-kpi--blue">
            <span class="ac-db-kpi__label">النقدية المتاحة</span>
            <strong>{{ $fmt($totalCash) }}</strong>
            <small>الخزنة {{ $fmtCompact($cashBalance) }} / البنك {{ $fmtCompact($bankBalance) }}</small>
        </article>

        <article class="ac-db-kpi ac-db-kpi--green">
            <span class="ac-db-kpi__label">دخل الشهر</span>
            <strong>{{ $fmt($revenueMonth) }}</strong>
            <small>اليوم {{ $fmt($revenueToday) }} / الأسبوع {{ $fmt($revenueWeek) }}</small>
        </article>

        <article class="ac-db-kpi ac-db-kpi--red">
            <span class="ac-db-kpi__label">مصروفات الشهر</span>
            <strong>{{ $fmt($expenseMonth) }}</strong>
            <small>اليوم {{ $fmt($expenseToday) }} / الأسبوع {{ $fmt($expenseWeek) }}</small>
        </article>

        <article class="ac-db-kpi ac-db-kpi--amber">
            <span class="ac-db-kpi__label">ربح الأسبوع</span>
            <strong>{{ $fmt($profitWeek) }}</strong>
            <small>ناتج الدخل ناقص المصروفات</small>
        </article>

        <article class="ac-db-kpi ac-db-kpi--cyan">
            <span class="ac-db-kpi__label">مستحق من العملاء</span>
            <strong>{{ $fmt($arOutstanding) }}</strong>
            <small>فواتير مبيعات غير محصلة</small>
        </article>

        <article class="ac-db-kpi ac-db-kpi--violet">
            <span class="ac-db-kpi__label">مستحق للموردين</span>
            <strong>{{ $fmt($apOutstanding) }}</strong>
            <small>فواتير شراء غير مسددة</small>
        </article>
    </section>

    <div class="ac-db-main-grid">
        <div class="ac-db-main">
            <section class="ac-db-card ac-db-card--chart">
                <div class="ac-db-card__head">
                    <div>
                        <span class="ac-db-eyebrow">آخر 12 شهر</span>
                        <h3>الدخل والمصروفات وصافي الربح</h3>
                    </div>
                    <a href="{{ route('accounting.reports.profit-loss') }}" class="ac-db-link">تقرير الربح والخسارة</a>
                </div>
                <div class="ac-db-chart">
                    <canvas id="chartRevExp"></canvas>
                </div>
            </section>

            <div class="ac-db-two-col">
                <section class="ac-db-card">
                    <div class="ac-db-card__head">
                        <div>
                            <span class="ac-db-eyebrow">هذا الشهر</span>
                            <h3>توزيع المصروفات</h3>
                        </div>
                    </div>

                    @if(! empty($breakdownData['values']))
                        <div class="ac-db-donut">
                            <canvas id="chartBreakdown"></canvas>
                        </div>
                        <div class="ac-db-legend" id="breakdownLegend"></div>
                    @else
                        <div class="ac-db-empty">لا توجد مصروفات كافية لعرض توزيع هذا الشهر.</div>
                    @endif
                </section>

                <section class="ac-db-card">
                    <div class="ac-db-card__head">
                        <div>
                            <span class="ac-db-eyebrow">أسابيع الشهر</span>
                            <h3>حركة أسبوعية</h3>
                        </div>
                    </div>
                    <div class="ac-db-chart ac-db-chart--small">
                        <canvas id="chartWeekly"></canvas>
                    </div>
                </section>
            </div>

            <section class="ac-db-card">
                <div class="ac-db-card__head">
                    <div>
                        <span class="ac-db-eyebrow">آخر العمليات</span>
                        <h3>المعاملات الحديثة</h3>
                    </div>
                    <a href="{{ route('accounting.transactions.index') }}" class="ac-db-link">عرض الكل</a>
                </div>

                <div class="ac-db-table-wrap">
                    <table class="ac-db-table">
                        <thead>
                            <tr>
                                <th>النوع</th>
                                <th>الوصف</th>
                                <th>من</th>
                                <th>إلى</th>
                                <th>المبلغ</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $tx)
                                @php
                                    $badgeClass = match($tx->type) {
                                        'income' => 'ac-db-badge--income',
                                        'expense' => 'ac-db-badge--expense',
                                        'transfer' => 'ac-db-badge--transfer',
                                        default => 'ac-db-badge--muted',
                                    };
                                @endphp
                                <tr>
                                    <td><span class="ac-db-badge {{ $badgeClass }}">{{ \App\Modules\Accounting\Models\Transaction::typeLabel($tx->type) }}</span></td>
                                    <td>{{ \Illuminate\Support\Str::limit($tx->description ?: 'بدون وصف', 38) }}</td>
                                    <td>{{ $tx->fromAccount?->name ?? '-' }}</td>
                                    <td>{{ $tx->toAccount?->name ?? '-' }}</td>
                                    <td class="ac-db-num {{ $tx->type === 'income' ? 'is-green' : ($tx->type === 'expense' ? 'is-red' : '') }}">{{ $fmt($tx->amount) }}</td>
                                    <td>{{ optional($tx->transaction_date)->format('Y/m/d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="ac-db-empty">لا توجد معاملات حديثة حتى الآن.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if($topCustomers->isNotEmpty())
                <section class="ac-db-card">
                    <div class="ac-db-card__head">
                        <div>
                            <span class="ac-db-eyebrow">متابعة التحصيل</span>
                            <h3>أهم العملاء</h3>
                        </div>
                        <a href="{{ route('accounting.customers.index') }}" class="ac-db-link">عرض العملاء</a>
                    </div>

                    <div class="ac-db-table-wrap">
                        <table class="ac-db-table">
                            <thead>
                                <tr>
                                    <th>العميل</th>
                                    <th>إجمالي الفواتير</th>
                                    <th>المحصل</th>
                                    <th>المستحق</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topCustomers as $customer)
                                    <tr>
                                        <td>
                                            <div class="ac-db-person">
                                                <span>{{ mb_substr($customer->name, 0, 1) }}</span>
                                                <b>{{ $customer->name }}</b>
                                            </div>
                                        </td>
                                        <td class="ac-db-num">{{ $fmt($customer->total_invoiced) }}</td>
                                        <td class="ac-db-num is-green">{{ $fmt($customer->total_paid) }}</td>
                                        <td class="ac-db-num {{ $customer->outstanding > 0 ? 'is-red' : '' }}">{{ $fmt($customer->outstanding) }}</td>
                                        <td><a href="{{ route('accounting.customers.show', $customer->id) }}" class="ac-db-link">عرض</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>

        <aside class="ac-db-side">
            @if(count($alerts) > 0)
                <section class="ac-db-card">
                    <div class="ac-db-card__head">
                        <h3>تنبيهات مهمة</h3>
                    </div>
                    <div class="ac-db-alert-list">
                        @foreach($alerts as $alert)
                            <a class="ac-db-alert ac-db-alert--{{ $alert['type'] }}" href="{{ $alert['link'] ?: '#' }}">
                                <strong>{{ $alert['title'] }}</strong>
                                <span>{{ $alert['message'] }}</span>
                                @if($alert['linkText'])
                                    <em>{{ $alert['linkText'] }}</em>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="ac-db-card">
                <div class="ac-db-card__head">
                    <h3>إجراءات سريعة</h3>
                </div>

                <div class="ac-db-actions">
                    @can('can-write')
                        <a href="{{ route('accounting.invoices.create') }}">فاتورة جديدة</a>
                        <a href="{{ route('accounting.transactions.create') }}">معاملة جديدة</a>
                        <a href="{{ route('accounting.customers.create') }}">عميل جديد</a>
                        <a href="{{ route('accounting.vendors.create') }}">مورد جديد</a>
                        <a href="{{ route('accounting.quotations.create') }}">عرض سعر</a>
                        <a href="{{ route('accounting.pos.create') }}">نقطة البيع</a>
                    @else
                        <span class="ac-db-empty">حسابك للعرض فقط.</span>
                    @endcan
                </div>
            </section>

            <section class="ac-db-card">
                <div class="ac-db-card__head">
                    <h3>توزيع النقدية</h3>
                </div>
                <div class="ac-db-cash-split">
                    <div>
                        <span>الخزنة</span>
                        <b>{{ $fmt($cashBalance) }}</b>
                        <div class="ac-db-progress" data-progress="{{ $cashShare }}"><span></span></div>
                    </div>
                    <div>
                        <span>البنك</span>
                        <b>{{ $fmt($bankBalance) }}</b>
                        <div class="ac-db-progress" data-progress="{{ $bankShare }}"><span></span></div>
                    </div>
                </div>
            </section>

            <section class="ac-db-card">
                <div class="ac-db-card__head">
                    <h3>تقارير سريعة</h3>
                </div>
                <div class="ac-db-report-links">
                    <a href="{{ route('accounting.reports.ar-aging') }}">
                        <span>الذمم المدينة</span>
                        <b>{{ $fmtCompact($arOutstanding) }}</b>
                    </a>
                    <a href="{{ route('accounting.reports.ap-aging') }}">
                        <span>الذمم الدائنة</span>
                        <b>{{ $fmtCompact($apOutstanding) }}</b>
                    </a>
                    <a href="{{ route('accounting.reports.income-expense') }}">
                        <span>الدخل والمصروف</span>
                        <b>تقرير</b>
                    </a>
                    <a href="{{ route('accounting.reports.trial-balance') }}">
                        <span>ميزان المراجعة</span>
                        <b>متقدم</b>
                    </a>
                </div>
            </section>

            <section class="ac-db-card">
                <div class="ac-db-card__head">
                    <h3>سجل النشاط</h3>
                </div>

                <div class="ac-db-activity">
                    @forelse($recentActivity as $log)
                        <div class="ac-db-activity__item ac-db-activity__item--{{ $log->action }}">
                            <span></span>
                            <div>
                                <strong>{{ \Illuminate\Support\Str::limit($log->description ?: ($log->entity_label ?? 'نشاط جديد'), 58) }}</strong>
                                <small>{{ $log->user_name ?? 'النظام' }} · {{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</small>
                            </div>
                        </div>
                    @empty
                        <div class="ac-db-empty">لا يوجد نشاط بعد.</div>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>

<script type="application/json" id="ac-dashboard-data">
    {!! $dashboardJson !!}
</script>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/accounting-dashboard.js') }}" defer></script>
@endpush

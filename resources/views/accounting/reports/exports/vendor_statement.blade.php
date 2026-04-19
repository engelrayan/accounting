@php
$fmt       = fn(float $n) => number_format($n, 2);
$fromHuman = \Carbon\Carbon::parse($from)->format('Y-m-d');
$toHuman   = \Carbon\Carbon::parse($to)->format('Y-m-d');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $report_title }}</title>
    <link rel="stylesheet" href="{{ public_path('css/report-export.css') }}">
    <style>
        body { font-size: 12px; }
        .stmt-opening { background: #f8fafc; font-style: italic; }
        .stmt-invoice td { background: #fff7ed; }
        .stmt-payment td { background: #f0fdf4; }
        .stmt-totals  td { background: #f1f5f9; font-weight: 700; border-top: 2px solid #cbd5e1; }
        .stmt-closing td { background: #1e3a5f; color: #fff; font-weight: 700; }
        .re-table td { padding: 6px 10px; }
        .num { text-align: left; font-variant-numeric: tabular-nums; direction: ltr; }
        .debit  { color: #b91c1c; font-weight: 600; }
        .credit { color: #15803d; font-weight: 600; }
    </style>
</head>
<body class="re-body">
<div class="re-shell">

    <header class="re-header">
        <div>
            <div class="re-kicker">كشف حساب مورد</div>
            <h1>{{ $vendor->name }}</h1>
            <p>من {{ $fromHuman }} إلى {{ $toHuman }}</p>
        </div>
        <div class="re-meta">
            @if($vendor->phone) <span>هاتف: {{ $vendor->phone }}</span> @endif
            @if($vendor->email) <span>{{ $vendor->email }}</span> @endif
            <span>تاريخ التصدير: {{ $exported_at->format('Y-m-d H:i') }}</span>
        </div>
    </header>

    {{-- Summary --}}
    <section class="re-card">
        <h2>ملخص الحساب</h2>
        <table class="re-table">
            <thead>
                <tr>
                    <th>الرصيد الافتتاحي</th>
                    <th>إجمالي الفواتير</th>
                    <th>إجمالي المدفوعات</th>
                    <th>الرصيد الختامي</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="num">{{ $fmt($opening_balance) }}</td>
                    <td class="num debit">{{ $fmt($total_credit) }}</td>
                    <td class="num credit">{{ $fmt($total_debit) }}</td>
                    <td class="num {{ $closing_balance > 0 ? 'debit' : ($closing_balance < 0 ? 'credit' : '') }}">
                        {{ $fmt($closing_balance) }}
                        @if($closing_balance > 0) (مستحق للمورد)
                        @elseif($closing_balance < 0) (رصيد لصالحنا)
                        @else (مسوَّى) @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </section>

    {{-- Ledger --}}
    <section class="re-card">
        <h2>حركات الحساب</h2>
        <table class="re-table">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>المرجع</th>
                    <th>البيان</th>
                    <th>دفعة (مدين)</th>
                    <th>فاتورة (دائن)</th>
                    <th>الرصيد</th>
                </tr>
            </thead>
            <tbody>
                <tr class="stmt-opening">
                    <td>{{ $fromHuman }}</td>
                    <td colspan="2">رصيد افتتاحي</td>
                    <td class="num">—</td>
                    <td class="num">—</td>
                    <td class="num">{{ $fmt($opening_balance) }}</td>
                </tr>

                @forelse($transactions as $txn)
                    <tr class="stmt-{{ $txn->type }}">
                        <td>{{ \Carbon\Carbon::parse($txn->event_date)->format('Y-m-d') }}</td>
                        <td>{{ $txn->reference }}</td>
                        <td>{{ $txn->description ?: '—' }}</td>
                        <td class="num {{ $txn->debit > 0 ? 'credit' : '' }}">
                            {{ $txn->debit > 0 ? $fmt($txn->debit) : '—' }}
                        </td>
                        <td class="num {{ $txn->credit > 0 ? 'debit' : '' }}">
                            {{ $txn->credit > 0 ? $fmt($txn->credit) : '—' }}
                        </td>
                        <td class="num">{{ $fmt($txn->balance) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:#64748b;padding:20px">
                            لا توجد حركات في هذه الفترة.
                        </td>
                    </tr>
                @endforelse

                <tr class="stmt-totals">
                    <td colspan="3">إجمالي الفترة</td>
                    <td class="num credit">{{ $fmt($total_debit) }}</td>
                    <td class="num debit">{{ $fmt($total_credit) }}</td>
                    <td></td>
                </tr>

                <tr class="stmt-closing">
                    <td colspan="5">الرصيد الختامي بتاريخ {{ $toHuman }}</td>
                    <td class="num">{{ $fmt($closing_balance) }}</td>
                </tr>
            </tbody>
        </table>
    </section>

</div>
</body>
</html>

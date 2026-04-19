@php
$fmt       = fn(float $n) => number_format($n, 2);
$fromHuman = \Carbon\Carbon::parse($from)->format('Y-m-d');
$toHuman   = \Carbon\Carbon::parse($to)->format('Y-m-d');
$netPositive = $netVat >= 0;

$statusLabels = [
    'paid'      => 'مدفوعة',
    'partial'   => 'جزئي',
    'pending'   => 'معلقة',
    'cancelled' => 'ملغاة',
];
$statusLabel = fn($s) => $statusLabels[$s] ?? $s;
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $report_title }}</title>
    <link rel="stylesheet" href="{{ public_path('css/report-export.css') }}">
    <style>
        body { font-size: 12px; }
        .num { text-align: left; font-variant-numeric: tabular-nums; direction: ltr; }
        .output-tax { color: #1d4ed8; font-weight: 600; }
        .input-tax  { color: #c2410c; font-weight: 600; }
        .net-pos    { color: #15803d; font-weight: 700; }
        .net-neg    { color: #b91c1c; font-weight: 700; }
        .re-table td { padding: 5px 10px; }
        .section-head {
            background: #f1f5f9;
            font-weight: 700;
            font-size: 13px;
            padding: 8px 12px;
            margin: 20px 0 8px;
            border-right: 4px solid #3b82f6;
        }
        .section-head.input { border-right-color: #f97316; }
        .tfoot-row td { background: #f8fafc; font-weight: 700; border-top: 2px solid #cbd5e1; }
        .net-box {
            margin-top: 24px;
            padding: 14px 18px;
            border-radius: 6px;
            background: #f0fdf4;
            border: 1px solid #86efac;
            font-size: 13px;
        }
        .net-box.refund { background: #fff7ed; border-color: #fdba74; }
        .net-box__title { font-weight: 700; margin-bottom: 8px; font-size: 14px; }
        .net-box__calc { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
    </style>
</head>
<body class="re-body">
<div class="re-shell">

    <header class="re-header">
        <div>
            <div class="re-kicker">تقرير ضريبة القيمة المضافة</div>
            <h1>VAT Report</h1>
            <p>من {{ $fromHuman }} إلى {{ $toHuman }}</p>
        </div>
        <div class="re-meta">
            <span>تاريخ التصدير: {{ $exported_at->format('Y-m-d H:i') }}</span>
        </div>
    </header>

    {{-- Summary --}}
    <section class="re-card">
        <h2>ملخص الضريبة</h2>
        <table class="re-table">
            <thead>
                <tr>
                    <th>ضريبة المخرجات (مبيعات)</th>
                    <th>ضريبة المدخلات (مشتريات)</th>
                    <th>صافي الضريبة</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="num output-tax">{{ $fmt($outputVat) }}</td>
                    <td class="num input-tax">{{ $fmt($inputVat) }}</td>
                    <td class="num {{ $netPositive ? 'net-pos' : 'net-neg' }}">{{ $fmt(abs($netVat)) }}</td>
                    <td>{{ $netPositive ? 'مستحق للسداد' : 'قابل للاسترداد' }}</td>
                </tr>
            </tbody>
        </table>
    </section>

    {{-- Sales Invoices --}}
    <div class="section-head">فواتير المبيعات — ضريبة المخرجات</div>
    @if($salesInvoices->isEmpty())
        <p style="color:#64748b;font-size:12px;padding:8px 0">لا توجد فواتير مبيعات بضريبة في هذه الفترة.</p>
    @else
        <table class="re-table">
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>التاريخ</th>
                    <th>العميل</th>
                    <th>الحالة</th>
                    <th class="num">قبل الضريبة</th>
                    <th class="num">نسبة الضريبة</th>
                    <th class="num">مبلغ الضريبة</th>
                    <th class="num">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesInvoices as $inv)
                    <tr>
                        <td>{{ $inv->invoice_number }}</td>
                        <td>{{ \Carbon\Carbon::parse($inv->issue_date)->format('Y-m-d') }}</td>
                        <td>{{ $inv->party_name }}</td>
                        <td>{{ $statusLabel($inv->status) }}</td>
                        <td class="num">{{ $fmt($inv->subtotal) }}</td>
                        <td class="num">{{ $inv->tax_rate }}%</td>
                        <td class="num output-tax">{{ $fmt($inv->tax_amount) }}</td>
                        <td class="num">{{ $fmt($inv->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-row">
                    <td colspan="4">الإجمالي ({{ $salesInvoices->count() }} فاتورة)</td>
                    <td class="num">{{ $fmt($salesInvoices->sum('subtotal')) }}</td>
                    <td class="num">—</td>
                    <td class="num output-tax">{{ $fmt($outputVat) }}</td>
                    <td class="num">{{ $fmt($salesInvoices->sum('amount')) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    {{-- Purchase Invoices --}}
    <div class="section-head input">فواتير المشتريات — ضريبة المدخلات</div>
    @if($purchaseInvoices->isEmpty())
        <p style="color:#64748b;font-size:12px;padding:8px 0">لا توجد فواتير مشتريات بضريبة في هذه الفترة.</p>
    @else
        <table class="re-table">
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>التاريخ</th>
                    <th>المورد</th>
                    <th>الحالة</th>
                    <th class="num">قبل الضريبة</th>
                    <th class="num">نسبة الضريبة</th>
                    <th class="num">مبلغ الضريبة</th>
                    <th class="num">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseInvoices as $inv)
                    <tr>
                        <td>{{ $inv->invoice_number }}</td>
                        <td>{{ \Carbon\Carbon::parse($inv->issue_date)->format('Y-m-d') }}</td>
                        <td>{{ $inv->party_name }}</td>
                        <td>{{ $statusLabel($inv->status) }}</td>
                        <td class="num">{{ $fmt($inv->subtotal) }}</td>
                        <td class="num">{{ $inv->tax_rate }}%</td>
                        <td class="num input-tax">{{ $fmt($inv->tax_amount) }}</td>
                        <td class="num">{{ $fmt($inv->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-row">
                    <td colspan="4">الإجمالي ({{ $purchaseInvoices->count() }} فاتورة)</td>
                    <td class="num">{{ $fmt($purchaseInvoices->sum('subtotal')) }}</td>
                    <td class="num">—</td>
                    <td class="num input-tax">{{ $fmt($inputVat) }}</td>
                    <td class="num">{{ $fmt($purchaseInvoices->sum('amount')) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    {{-- Net box --}}
    <div class="net-box {{ !$netPositive ? 'refund' : '' }}">
        <div class="net-box__title">
            {{ $netPositive ? 'صافي الضريبة المستحقة للسداد' : 'صافي الضريبة القابلة للاسترداد' }}
        </div>
        <div class="net-box__calc">
            <span>ضريبة المخرجات: <strong class="output-tax">{{ $fmt($outputVat) }}</strong></span>
            <span>−</span>
            <span>ضريبة المدخلات: <strong class="input-tax">{{ $fmt($inputVat) }}</strong></span>
            <span>=</span>
            <span class="{{ $netPositive ? 'net-pos' : 'net-neg' }}">
                {{ $fmt(abs($netVat)) }}
                {{ !$netPositive ? '(استرداد)' : '' }}
            </span>
        </div>
    </div>

</div>
</body>
</html>

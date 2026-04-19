@php
    $fmt      = fn(float $n) => number_format($n, 2);
    $asOfHuman = \Carbon\Carbon::parse($as_of)->format('Y-m-d');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $report_title }}</title>
    <link rel="stylesheet" href="{{ public_path('css/report-export.css') }}">
    <style>
        .num  { text-align: left; direction: ltr; }
        .col-current { color: #16a34a; }
        .col-b1   { color: #ca8a04; }
        .col-b2   { color: #ea580c; }
        .col-b3   { color: #dc2626; }
        .col-b4   { color: #7f1d1d; font-weight: 700; }
        .row-totals td { font-weight: 700; background: #f1f5f9; border-top: 2px solid #cbd5e1; }
        .re-table td, .re-table th { padding: 7px 10px; }
        .summary-grid { display: table; width: 100%; margin-bottom: 14px; }
        .summary-grid > div { display: table-cell; text-align: center; padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px; }
        .summary-val { font-size: 20px; font-weight: 700; }
        .summary-lbl { font-size: 10px; color: #64748b; margin-top: 2px; }
    </style>
</head>
<body class="re-body">
<div class="re-shell re-shell--wide">

    <header class="re-header">
        <div>
            <div class="re-kicker">الذمم الدائنة</div>
            <h1>{{ $report_title }}</h1>
            <p>بتاريخ: {{ $asOfHuman }}</p>
        </div>
        <div class="re-meta">
            <span>تاريخ التصدير: {{ $exported_at->format('Y-m-d H:i') }}</span>
        </div>
    </header>

    {{-- Summary --}}
    <section class="re-card">
        <div class="summary-grid">
            <div>
                <div class="summary-val" style="color:#7c3aed">{{ $fmt($total_outstanding) }}</div>
                <div class="summary-lbl">إجمالي المستحقات للموردين</div>
            </div>
            <div>
                <div class="summary-val" style="color:#dc2626">{{ $fmt($total_overdue) }}</div>
                <div class="summary-lbl">إجمالي المتأخرة</div>
            </div>
            <div>
                <div class="summary-val">{{ $vendor_count }}</div>
                <div class="summary-lbl">عدد الموردين</div>
            </div>
            <div>
                <div class="summary-val" style="color:#dc2626">{{ $max_days_overdue }}</div>
                <div class="summary-lbl">أقصى أيام تأخر</div>
            </div>
        </div>
    </section>

    {{-- Aging Table --}}
    <section class="re-card">
        <h2>تفاصيل التقادم حسب المورد</h2>
        <table class="re-table">
            <thead>
                <tr>
                    <th>المورد</th>
                    <th>فواتير</th>
                    <th class="num col-current">حالية</th>
                    <th class="num col-b1">1-30 يوم</th>
                    <th class="num col-b2">31-60 يوم</th>
                    <th class="num col-b3">61-90 يوم</th>
                    <th class="num col-b4">+91 يوم</th>
                    <th class="num">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td>{{ $row->vendor_name }}</td>
                    <td style="text-align:center">{{ $row->invoice_count }}</td>
                    <td class="num col-current">{{ $row->current > 0 ? $fmt($row->current) : '—' }}</td>
                    <td class="num col-b1">{{ $row->b1_30 > 0 ? $fmt($row->b1_30) : '—' }}</td>
                    <td class="num col-b2">{{ $row->b31_60 > 0 ? $fmt($row->b31_60) : '—' }}</td>
                    <td class="num col-b3">{{ $row->b61_90 > 0 ? $fmt($row->b61_90) : '—' }}</td>
                    <td class="num col-b4">{{ $row->b91plus > 0 ? $fmt($row->b91plus) : '—' }}</td>
                    <td class="num" style="font-weight:700">{{ $fmt($row->total) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;color:#64748b;padding:20px">
                        لا توجد مستحقات مفتوحة بتاريخ {{ $asOfHuman }}.
                    </td>
                </tr>
                @endforelse

                @if($rows->isNotEmpty())
                <tr class="row-totals">
                    <td>الإجمالي</td>
                    <td></td>
                    <td class="num col-current">{{ $fmt($totals->current) }}</td>
                    <td class="num col-b1">{{ $fmt($totals->b1_30) }}</td>
                    <td class="num col-b2">{{ $fmt($totals->b31_60) }}</td>
                    <td class="num col-b3">{{ $fmt($totals->b61_90) }}</td>
                    <td class="num col-b4">{{ $fmt($totals->b91plus) }}</td>
                    <td class="num">{{ $fmt($totals->total) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </section>

</div>
</body>
</html>

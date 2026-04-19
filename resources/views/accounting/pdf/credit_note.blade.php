@php
    $fmt = fn(float $n) => number_format($n, 2);
    $settings = $creditNote->_pdfSettings ?? null;
    $companyName    = $settings?->get('company_name')    ?? 'محاسب عام';
    $companyAddress = $settings?->get('company_address') ?? '';
    $taxName        = $settings?->taxName()              ?? 'ضريبة القيمة المضافة';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إشعار دائن {{ $creditNote->credit_note_number }}</title>
    <link rel="stylesheet" href="{{ public_path('css/report-export.css') }}">
    <style>
        .inv-doc { border: 2px solid #d97706; border-radius: 8px; padding: 28px; }
        .inv-header { display: table; width: 100%; margin-bottom: 20px; border-bottom: 2px solid #d97706; padding-bottom: 16px; }
        .inv-header > div { display: table-cell; vertical-align: top; }
        .inv-header > div:last-child { text-align: left; }
        .inv-company { font-size: 20px; font-weight: 700; color: #d97706; margin-bottom: 4px; }
        .inv-company-sub { font-size: 11px; color: #6b7280; }
        .inv-type { font-size: 13px; font-weight: 700; color: #d97706; letter-spacing: 1px; margin-bottom: 4px; }
        .inv-number { font-size: 22px; font-weight: 700; color: #111827; }
        .inv-status { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
                      background: #fef3c7; color: #d97706; margin-top: 4px; }
        .inv-info { display: table; width: 100%; margin: 16px 0; }
        .inv-info > div { display: table-cell; vertical-align: top; width: 50%; }
        .inv-label { font-size: 10px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
        .inv-party-name { font-size: 16px; font-weight: 700; color: #111827; }
        .inv-party-detail { font-size: 11px; color: #4b5563; margin-top: 2px; }
        .inv-dates { text-align: left; }
        .inv-date-row { margin-bottom: 4px; }
        .inv-date-row span:first-child { color: #6b7280; font-size: 11px; margin-left: 8px; }
        .inv-date-row span:last-child { font-weight: 600; font-size: 12px; }
        .inv-reason { margin: 14px 0; padding: 10px 14px; background: #fffbeb; border-radius: 6px; border-right: 3px solid #d97706; font-size: 11px; color: #374151; }
        .inv-reason strong { color: #d97706; margin-left: 6px; }
        table.inv-items { width: 100%; border-collapse: collapse; margin: 16px 0; }
        table.inv-items th { background: #d97706; color: #fff; padding: 8px 10px; font-size: 11px; text-align: right; }
        table.inv-items td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        table.inv-items tbody tr:nth-child(even) td { background: #fffbeb; }
        .num { text-align: left; direction: ltr; }
        .inv-amount-box { margin-top: 16px; padding: 16px; background: #fffbeb; border: 2px solid #d97706; border-radius: 8px; text-align: center; }
        .inv-amount-label { font-size: 11px; color: #92400e; margin-bottom: 4px; }
        .inv-amount-value { font-size: 28px; font-weight: 700; color: #d97706; }
        .inv-amount-tax { font-size: 11px; color: #6b7280; margin-top: 4px; }
        .inv-footer-stamp { margin-top: 40px; display: table; width: 100%; }
        .inv-footer-stamp > div { display: table-cell; text-align: center; color: #9ca3af; font-size: 10px; border-top: 1px dashed #d1d5db; padding-top: 8px; width: 33%; }
    </style>
</head>
<body class="re-body">
<div class="re-shell">

    <div class="inv-doc">

        {{-- Header --}}
        <div class="inv-header">
            <div>
                <div class="inv-company">{{ $companyName }}</div>
                @if($companyAddress)
                    <div class="inv-company-sub">{{ $companyAddress }}</div>
                @endif
            </div>
            <div>
                <div class="inv-type">إشعار دائن</div>
                <div class="inv-number">{{ $creditNote->credit_note_number }}</div>
                <div class="inv-status">{{ $creditNote->statusLabel() }}</div>
            </div>
        </div>

        {{-- Customer + Dates --}}
        <div class="inv-info">
            <div>
                <div class="inv-label">بيانات العميل</div>
                <div class="inv-party-name">{{ $creditNote->customer?->name ?? '—' }}</div>
                @if($creditNote->customer?->phone)
                    <div class="inv-party-detail">{{ $creditNote->customer->phone }}</div>
                @endif
                @if($creditNote->customer?->email)
                    <div class="inv-party-detail">{{ $creditNote->customer->email }}</div>
                @endif
            </div>
            <div class="inv-dates">
                <div class="inv-date-row">
                    <span>تاريخ الإشعار</span>
                    <span>{{ $creditNote->issue_date->format('Y/m/d') }}</span>
                </div>
                <div class="inv-date-row">
                    <span>الفاتورة المرجعية</span>
                    <span>{{ $creditNote->invoice?->invoice_number ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- Reason --}}
        @if($creditNote->reason)
        <div class="inv-reason">
            <strong>السبب:</strong>{{ $creditNote->reason }}
        </div>
        @endif

        {{-- Items from original invoice --}}
        @if($creditNote->invoice?->items?->isNotEmpty())
        <table class="inv-items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>البيان (الفاتورة الأصلية)</th>
                    <th class="num">الكمية</th>
                    <th class="num">سعر الوحدة</th>
                    <th class="num">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($creditNote->invoice->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
                    <td class="num">{{ $fmt($item->unit_price) }}</td>
                    <td class="num" style="font-weight:700">{{ $fmt($item->total) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Amount Box --}}
        <div class="inv-amount-box">
            <div class="inv-amount-label">إجمالي مبلغ الإشعار الدائن</div>
            <div class="inv-amount-value">{{ $fmt($creditNote->total) }}</div>
            @if((float)$creditNote->tax_amount > 0)
                <div class="inv-amount-tax">
                    شامل {{ $taxName }}: {{ $fmt($creditNote->tax_amount) }}
                    (صافي: {{ $fmt($creditNote->amount) }})
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="inv-footer-stamp">
            <div>تم الإصدار بواسطة<br>{{ $companyName }}</div>
            <div>{{ $creditNote->credit_note_number }}<br>{{ now()->format('Y-m-d') }}</div>
            <div>توقيع المعتمد<br>&nbsp;</div>
        </div>

    </div>

</div>
</body>
</html>

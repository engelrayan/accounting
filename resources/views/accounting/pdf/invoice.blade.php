@php
    $fmt = fn(float $n) => number_format($n, 2);
    $settings = $invoice->_pdfSettings ?? null;
    $companyName    = $settings?->get('company_name')    ?? 'محاسب عام';
    $companyAddress = $settings?->get('company_address') ?? '';
    $taxName        = $settings?->taxName()              ?? 'ضريبة القيمة المضافة';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة {{ $invoice->invoice_number }}</title>
    <link rel="stylesheet" href="{{ public_path('css/report-export.css') }}">
    <style>
        .inv-doc { border: 1px solid #dbe1ea; border-radius: 8px; padding: 28px; }
        .inv-header { display: table; width: 100%; margin-bottom: 20px; border-bottom: 2px solid #1d4ed8; padding-bottom: 16px; }
        .inv-header > div { display: table-cell; vertical-align: top; }
        .inv-header > div:last-child { text-align: left; }
        .inv-company { font-size: 20px; font-weight: 700; color: #1d4ed8; margin-bottom: 4px; }
        .inv-company-sub { font-size: 11px; color: #6b7280; }
        .inv-number { font-size: 22px; font-weight: 700; color: #111827; }
        .inv-status { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
                      background: #dbeafe; color: #1d4ed8; margin-top: 4px; }
        .inv-info { display: table; width: 100%; margin: 16px 0; }
        .inv-info > div { display: table-cell; vertical-align: top; width: 50%; }
        .inv-label { font-size: 10px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
        .inv-party-name { font-size: 16px; font-weight: 700; color: #111827; }
        .inv-party-detail { font-size: 11px; color: #4b5563; margin-top: 2px; }
        .inv-dates { text-align: left; }
        .inv-date-row { margin-bottom: 4px; }
        .inv-date-row span:first-child { color: #6b7280; font-size: 11px; margin-left: 8px; }
        .inv-date-row span:last-child { font-weight: 600; font-size: 12px; }
        table.inv-items { width: 100%; border-collapse: collapse; margin: 16px 0; }
        table.inv-items th { background: #1d4ed8; color: #fff; padding: 8px 10px; font-size: 11px; text-align: right; }
        table.inv-items td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        table.inv-items tbody tr:nth-child(even) td { background: #f8fafc; }
        .num { text-align: left; direction: ltr; }
        .inv-footer { display: table; width: 100%; margin-top: 16px; }
        .inv-footer > div { display: table-cell; vertical-align: top; }
        .inv-totals { width: 52%; text-align: left; }
        .inv-total-row { display: table; width: 100%; padding: 5px 0; border-bottom: 1px solid #e5e7eb; }
        .inv-total-row > span { display: table-cell; }
        .inv-total-row > span:last-child { text-align: left; direction: ltr; font-weight: 600; }
        .inv-total-grand { font-size: 15px; font-weight: 700; color: #1d4ed8; border-top: 2px solid #1d4ed8 !important; border-bottom: none !important; padding-top: 8px; }
        .inv-total-danger { color: #dc2626; }
        .inv-total-success { color: #16a34a; }
        .inv-notes { margin-top: 20px; padding: 12px; background: #f8fafc; border-radius: 6px; border-right: 3px solid #1d4ed8; }
        .inv-notes-label { font-size: 10px; color: #6b7280; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; }
        .inv-notes-text { font-size: 11px; color: #374151; }
        .inv-footer-stamp { margin-top: 40px; display: table; width: 100%; }
        .inv-footer-stamp > div { display: table-cell; text-align: center; color: #9ca3af; font-size: 10px; border-top: 1px dashed #d1d5db; padding-top: 8px; width: 33%; }
    </style>
</head>
<body class="re-body">
<div class="re-shell">

    <div class="inv-doc">

        {{-- Header: Company + Invoice Number --}}
        <div class="inv-header">
            <div>
                <div class="inv-company">{{ $companyName }}</div>
                @if($companyAddress)
                    <div class="inv-company-sub">{{ $companyAddress }}</div>
                @endif
            </div>
            <div>
                <div class="inv-number">{{ $invoice->invoice_number }}</div>
                <div class="inv-status">{{ $invoice->statusLabel() }}</div>
            </div>
        </div>

        {{-- Customer + Dates --}}
        <div class="inv-info">
            <div>
                <div class="inv-label">إلى</div>
                <div class="inv-party-name">{{ $invoice->customer->name }}</div>
                @if($invoice->customer->phone)
                    <div class="inv-party-detail">{{ $invoice->customer->phone }}</div>
                @endif
                @if($invoice->customer->email)
                    <div class="inv-party-detail">{{ $invoice->customer->email }}</div>
                @endif
                @if($invoice->customer->address)
                    <div class="inv-party-detail">{{ $invoice->customer->address }}</div>
                @endif
            </div>
            <div class="inv-dates">
                <div class="inv-date-row">
                    <span>تاريخ الإصدار</span>
                    <span>{{ $invoice->issue_date->format('Y/m/d') }}</span>
                </div>
                @if($invoice->due_date)
                <div class="inv-date-row">
                    <span>تاريخ الاستحقاق</span>
                    <span>{{ $invoice->due_date->format('Y/m/d') }}</span>
                </div>
                @endif
                @if($invoice->payment_method)
                <div class="inv-date-row">
                    <span>طريقة الدفع</span>
                    <span>{{ $invoice->paymentMethodLabel() }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Items Table --}}
        <table class="inv-items">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الوصف</th>
                    <th class="num">الكمية</th>
                    <th class="num">سعر الوحدة</th>
                    <th class="num">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $i => $item)
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

        {{-- Totals --}}
        <div class="inv-footer">
            <div></div>
            <div class="inv-totals">
                @if((float)$invoice->tax_amount > 0)
                <div class="inv-total-row">
                    <span>المجموع قبل الضريبة</span>
                    <span>{{ $fmt($invoice->subtotal) }}</span>
                </div>
                @if((float)$invoice->discount_amount > 0)
                <div class="inv-total-row">
                    <span>الخصم</span>
                    <span>-{{ $fmt($invoice->discount_amount) }}</span>
                </div>
                @endif
                <div class="inv-total-row">
                    <span>{{ $taxName }} ({{ rtrim(rtrim(number_format((float)$invoice->tax_rate, 2),'0'),'.') }}%)</span>
                    <span>{{ $fmt($invoice->tax_amount) }}</span>
                </div>
                @endif
                @if((float)$invoice->tax_amount <= 0 && (float)$invoice->discount_amount > 0)
                <div class="inv-total-row">
                    <span>الإجمالي قبل الخصم</span>
                    <span>{{ $fmt($invoice->subtotal) }}</span>
                </div>
                <div class="inv-total-row">
                    <span>الخصم</span>
                    <span>-{{ $fmt($invoice->discount_amount) }}</span>
                </div>
                @endif
                <div class="inv-total-row inv-total-grand">
                    <span>الإجمالي</span>
                    <span>{{ $fmt($invoice->amount) }}</span>
                </div>
                <div class="inv-total-row">
                    <span>المدفوع</span>
                    <span class="inv-total-success">{{ $fmt($invoice->paid_amount) }}</span>
                </div>
                <div class="inv-total-row inv-total-grand {{ $invoice->remaining_amount > 0 ? 'inv-total-danger' : 'inv-total-success' }}">
                    <span>المتبقي</span>
                    <span>{{ $fmt($invoice->remaining_amount) }}</span>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        @if($invoice->notes)
        <div class="inv-notes">
            <div class="inv-notes-label">ملاحظات</div>
            <div class="inv-notes-text">{{ $invoice->notes }}</div>
        </div>
        @endif

        {{-- Footer --}}
        <div class="inv-footer-stamp">
            <div>تم الإصدار بواسطة<br>{{ $companyName }}</div>
            <div>{{ $invoice->invoice_number }}<br>{{ now()->format('Y-m-d') }}</div>
            <div>توقيع المستلم<br>&nbsp;</div>
        </div>

    </div>

</div>
</body>
</html>

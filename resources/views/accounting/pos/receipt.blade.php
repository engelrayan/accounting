<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إيصال POS {{ $invoice->invoice_number }}</title>
    <style>
        :root {
            color-scheme: light;
        }
        body {
            margin: 0;
            background: #eef2f7;
            font-family: "Cairo", Arial, sans-serif;
            color: #0f172a;
        }
        .receipt-shell {
            max-width: 380px;
            margin: 24px auto;
            padding: 16px;
        }
        .receipt-actions {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }
        .receipt-actions a,
        .receipt-actions button {
            flex: 1;
            min-height: 42px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            background: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            color: #0f172a;
        }
        .receipt-actions button.primary {
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: #fff;
        }
        .receipt-note {
            margin: 0 0 12px;
            padding: 10px 12px;
            border-radius: 12px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            line-height: 1.6;
        }
        .receipt {
            width: 80mm;
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 16px 36px rgba(15,23,42,.08);
            padding: 18px 16px;
        }
        .receipt h1 {
            margin: 0;
            text-align: center;
            font-size: 20px;
            font-weight: 800;
        }
        .receipt-sub {
            margin-top: 4px;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }
        .receipt-pill {
            margin: 12px auto 0;
            display: block;
            width: fit-content;
            padding: 4px 10px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 800;
        }
        .receipt-meta,
        .receipt-totals,
        .receipt-line {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-size: 13px;
        }
        .receipt-meta {
            margin-top: 14px;
            color: #334155;
        }
        .receipt-divider {
            margin: 14px 0;
            border-top: 1px dashed #cbd5e1;
        }
        .receipt-items {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .receipt-item {
            padding-bottom: 10px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .receipt-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }
        .receipt-item__name {
            font-weight: 800;
            margin-bottom: 6px;
        }
        .receipt-item__meta {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            color: #64748b;
            font-size: 12px;
        }
        .receipt-totals {
            padding: 4px 0;
        }
        .receipt-totals.total {
            margin-top: 6px;
            padding-top: 10px;
            border-top: 1px solid #cbd5e1;
            font-size: 16px;
            font-weight: 800;
        }
        .receipt-footer {
            margin-top: 16px;
            text-align: center;
            color: #64748b;
            font-size: 11px;
            line-height: 1.7;
        }
        .receipt-cashier {
            margin-top: 8px;
            text-align: center;
            font-size: 12px;
            color: #334155;
        }
        @media print {
            body {
                background: #fff;
            }
            .receipt-shell {
                max-width: none;
                margin: 0;
                padding: 0;
            }
            .receipt-actions,
            .receipt-note {
                display: none !important;
            }
            .receipt {
                width: 80mm;
                box-shadow: none;
                border-radius: 0;
                padding: 0;
            }
            @page {
                size: 80mm auto;
                margin: 6mm;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-shell">
        <div class="receipt-actions">
            <button type="button" class="primary" onclick="window.print()">طباعة الإيصال</button>
            <a href="{{ route('accounting.pos.drawer') }}" target="_blank">فتح درج الكاشير</a>
            <a href="{{ route('accounting.invoices.show', $invoice) }}">عرض الفاتورة</a>
        </div>

        <p class="receipt-note">
            إذا كان درج الكاشير مربوطًا بالطابعة الحرارية فغالبًا سيفتح مع أمر الطباعة حسب إعدادات الطابعة المحلية.
        </p>

        <div class="receipt">
            <h1>محاسب عام</h1>
            <div class="receipt-sub">إيصال نقطة بيع</div>
            <span class="receipt-pill">{{ $invoice->invoice_number }}</span>

            <div class="receipt-meta">
                <span>التاريخ</span>
                <span>{{ $invoice->issue_date->format('Y/m/d') }}</span>
            </div>
            <div class="receipt-meta">
                <span>الوقت</span>
                <span>{{ $invoice->created_at->format('H:i') }}</span>
            </div>
            <div class="receipt-meta">
                <span>العميل</span>
                <span>{{ $invoice->customer->name }}</span>
            </div>
            <div class="receipt-meta">
                <span>الدفع</span>
                <span>{{ $invoice->paymentMethodLabel() }}</span>
            </div>

            <div class="receipt-divider"></div>

            <div class="receipt-items">
                @foreach($invoice->items as $item)
                    <div class="receipt-item">
                        <div class="receipt-item__name">{{ $item->description }}</div>
                        <div class="receipt-item__meta">
                            <span>{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }} × {{ number_format($item->unit_price, 2) }}</span>
                            <strong>{{ number_format($item->total, 2) }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="receipt-divider"></div>

            <div class="receipt-totals">
                <span>قبل الخصم</span>
                <strong>{{ number_format($invoice->subtotal, 2) }}</strong>
            </div>
            @if((float) $invoice->discount_amount > 0)
                <div class="receipt-totals">
                    <span>الخصم</span>
                    <strong>-{{ number_format($invoice->discount_amount, 2) }}</strong>
                </div>
            @endif
            @if((float) $invoice->tax_amount > 0)
                <div class="receipt-totals">
                    <span>الضريبة</span>
                    <strong>{{ number_format($invoice->tax_amount, 2) }}</strong>
                </div>
            @endif
            <div class="receipt-totals total">
                <span>الإجمالي</span>
                <strong>{{ number_format($invoice->amount, 2) }}</strong>
            </div>

            <div class="receipt-cashier">
                الكاشير: {{ $invoice->creator?->name ?? 'غير محدد' }}
            </div>

            <div class="receipt-footer">
                شكرًا لتعاملكم معنا<br>
                تم إنشاء العملية وربطها تلقائيًا بالحركة المحاسبية والمخزون.
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            const autoPrint = new URLSearchParams(window.location.search).get('print');
            if (autoPrint !== '0') {
                setTimeout(() => window.print(), 250);
            }
        });
    </script>
</body>
</html>

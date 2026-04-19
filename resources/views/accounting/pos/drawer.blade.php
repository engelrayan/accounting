<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فتح درج الكاشير</title>
    <style>
        body {
            margin: 0;
            background: #eef2f7;
            font-family: "Cairo", Arial, sans-serif;
            color: #0f172a;
        }
        .drawer-shell {
            max-width: 360px;
            margin: 32px auto;
            padding: 16px;
        }
        .drawer-card {
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.1);
            padding: 24px 20px;
            text-align: center;
        }
        .drawer-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            border-radius: 20px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            color: #fff;
        }
        .drawer-title {
            margin: 0 0 8px;
            font-size: 24px;
            font-weight: 800;
        }
        .drawer-text {
            margin: 0;
            color: #475569;
            line-height: 1.8;
            font-size: 14px;
        }
        .drawer-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }
        .drawer-actions a,
        .drawer-actions button {
            flex: 1;
            min-height: 44px;
            border: 0;
            border-radius: 14px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }
        .drawer-actions button {
            background: #1d4ed8;
            color: #fff;
        }
        .drawer-actions a {
            background: #e2e8f0;
            color: #0f172a;
            display: grid;
            place-items: center;
        }
        .drawer-print {
            margin-top: 18px;
            padding: 12px;
            border-radius: 14px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            line-height: 1.8;
        }
        @media print {
            body {
                background: #fff;
            }
            .drawer-shell {
                margin: 0;
                padding: 0;
            }
            .drawer-actions,
            .drawer-print {
                display: none !important;
            }
            .drawer-card {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
            }
            @page {
                size: 80mm auto;
                margin: 4mm;
            }
        }
    </style>
</head>
<body>
    <div class="drawer-shell">
        <div class="drawer-card">
            <div class="drawer-icon">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 7h18"/>
                    <path d="M5 7v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7"/>
                    <path d="M8 12h8"/>
                    <path d="M10 16h4"/>
                </svg>
            </div>

            <h1 class="drawer-title">فتح درج الكاشير</h1>
            <p class="drawer-text">
                عند طباعة هذه الصفحة على الطابعة الحرارية، قد يفتح درج الكاشير تلقائيًا إذا كانت الطابعة المحلية
                مهيأة لفتح الدرج مع أمر الطباعة.
            </p>

            <div class="drawer-actions">
                <button type="button" onclick="window.print()">طباعة الآن</button>
                <a href="{{ route('accounting.pos.create') }}">العودة إلى POS</a>
            </div>

            <div class="drawer-print">
                هذه الصفحة لا تتحكم مباشرة في الهاردوير من المتصفح، لكنها توفّر أسرع تدفق عملي متاح للطابعة الحرارية.
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            if (new URLSearchParams(window.location.search).get('print') !== '0') {
                setTimeout(() => window.print(), 250);
            }
        });
    </script>
</body>
</html>

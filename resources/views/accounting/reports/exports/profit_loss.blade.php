@php
$fmt = fn(float $n) => number_format($n, 2);
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $report_title }}</title>
    <link rel="stylesheet" href="{{ public_path('css/report-export.css') }}">
</head>
<body class="re-body">
<div class="re-shell">
    <header class="re-header">
        <div>
            <div class="re-kicker">محاسب عام</div>
            <h1>{{ $report_title }}</h1>
            <p>{{ $label }} — {{ $human_range }}</p>
        </div>
        <div class="re-meta">
            <span>تاريخ التصدير: {{ $exported_at->format('Y-m-d H:i') }}</span>
        </div>
    </header>

    <section class="re-card">
        <h2>الملخص</h2>
        <table class="re-table">
            <thead>
            <tr>
                <th>إجمالي الإيرادات</th>
                <th>إجمالي المصروفات</th>
                <th>صافي الربح / الخسارة</th>
                <th>هامش الربح</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{ $fmt($total_revenue) }}</td>
                <td>{{ $fmt($total_expenses) }}</td>
                <td>{{ $fmt($net_profit) }}</td>
                <td>{{ $margin_pct }}%</td>
            </tr>
            </tbody>
        </table>
        <p class="re-note">{{ $net_reason }}</p>
    </section>

    <section class="re-grid">
        <div class="re-card">
            <h2>الإيرادات</h2>
            <table class="re-table">
                <thead>
                <tr>
                    <th>الكود</th>
                    <th>الحساب</th>
                    <th>النسبة</th>
                    <th>المبلغ</th>
                </tr>
                </thead>
                <tbody>
                @forelse($revenue_rows as $row)
                    <tr>
                        <td>{{ $row->code }}</td>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->pct }}%</td>
                        <td>{{ $fmt($row->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">لا توجد إيرادات في هذه الفترة.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="re-card">
            <h2>المصروفات</h2>
            <table class="re-table">
                <thead>
                <tr>
                    <th>الكود</th>
                    <th>الحساب</th>
                    <th>النسبة</th>
                    <th>المبلغ</th>
                </tr>
                </thead>
                <tbody>
                @forelse($expense_rows as $row)
                    <tr>
                        <td>{{ $row->code }}</td>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->pct }}%</td>
                        <td>{{ $fmt($row->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">لا توجد مصروفات في هذه الفترة.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($has_comparison)
        <section class="re-card">
            <h2>مقارنة مع الفترة السابقة</h2>
            <table class="re-table">
                <thead>
                <tr>
                    <th>الفترة السابقة</th>
                    <th>الإيرادات</th>
                    <th>المصروفات</th>
                    <th>الصافي</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>{{ $prev_from }} - {{ $prev_to }}</td>
                    <td>{{ $fmt($prev_revenue) }}</td>
                    <td>{{ $fmt($prev_expenses) }}</td>
                    <td>{{ $fmt($prev_net_profit) }}</td>
                </tr>
                </tbody>
            </table>
        </section>
    @endif

    @if(count($insights) > 0)
        <section class="re-card">
            <h2>ملاحظات ذكية</h2>
            <ul class="re-list">
                @foreach($insights as $insight)
                    <li>
                        <strong>{{ $insight['message'] }}</strong>
                        @if(!empty($insight['suggestion']))
                            <span>{{ $insight['suggestion'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
</body>
</html>

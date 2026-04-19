@php
$fmt = fn(float $n) => number_format(abs($n), 2);
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $report_title }}</title>
    <link rel="stylesheet" href="{{ public_path('css/report-export.css') }}">
</head>
<body class="re-body">
<div class="re-shell re-shell--wide">
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
                <th>إجمالي المدين</th>
                <th>إجمالي الدائن</th>
                <th>الفرق</th>
                <th>الحالة</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{ number_format($total_debit, 2) }}</td>
                <td>{{ number_format($total_credit, 2) }}</td>
                <td>{{ number_format($difference, 2) }}</td>
                <td>{{ $is_balanced ? 'متوازن' : 'غير متوازن' }}</td>
            </tr>
            </tbody>
        </table>
    </section>

    @foreach($groups as $group)
        <section class="re-card">
            <h2>{{ $group['label'] }}</h2>
            <table class="re-table">
                <thead>
                <tr>
                    <th>الكود</th>
                    <th>الحساب</th>
                    <th>النوع</th>
                    <th>الرصيد</th>
                    <th>الاتجاه الطبيعي</th>
                </tr>
                </thead>
                <tbody>
                @foreach($group['accounts'] as $row)
                    <tr>
                        <td>{{ $row->code }}</td>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->type }}</td>
                        <td>{{ $fmt($row->net_balance) }}</td>
                        <td>{{ $row->normal_balance === 'debit' ? 'مدين' : 'دائن' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>
    @endforeach
</div>
</body>
</html>

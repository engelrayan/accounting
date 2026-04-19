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
                <th>إجمالي الدخل</th>
                <th>إجمالي المصروف</th>
                <th>صافي النشاط</th>
                <th>متوسط الدخل اليومي</th>
                <th>متوسط المصروف اليومي</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{ $fmt($total_income) }}</td>
                <td>{{ $fmt($total_expenses) }}</td>
                <td>{{ $fmt($net_result) }}</td>
                <td>{{ $fmt($average_income) }}</td>
                <td>{{ $fmt($average_expense) }}</td>
            </tr>
            </tbody>
        </table>
    </section>

    <section class="re-card">
        <h2>الحركة خلال الفترة</h2>
        <table class="re-table">
            <thead>
            <tr>
                <th>الفترة</th>
                <th>المدى</th>
                <th>الدخل</th>
                <th>المصروف</th>
                <th>الصافي</th>
            </tr>
            </thead>
            <tbody>
            @foreach($series as $bucket)
                <tr>
                    <td>{{ $bucket->label }}</td>
                    <td>{{ $bucket->range }}</td>
                    <td>{{ $fmt($bucket->income) }}</td>
                    <td>{{ $fmt($bucket->expense) }}</td>
                    <td>{{ $fmt($bucket->net) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <section class="re-grid">
        <div class="re-card">
            <h2>بنود الدخل</h2>
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
                @forelse($income_rows as $row)
                    <tr>
                        <td>{{ $row->code }}</td>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->pct }}%</td>
                        <td>{{ $fmt($row->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">لا توجد بيانات دخل في هذه الفترة.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="re-card">
            <h2>بنود المصروف</h2>
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
                    <tr><td colspan="4">لا توجد بيانات مصروف في هذه الفترة.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

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

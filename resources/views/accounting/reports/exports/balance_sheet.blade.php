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

    {{-- ── Header ── --}}
    <header class="re-header">
        <div>
            <div class="re-kicker">محاسب عام</div>
            <h1>{{ $report_title }}</h1>
            <p>حتى تاريخ: {{ \Carbon\Carbon::parse($as_of)->translatedFormat('j F Y') }}</p>
        </div>
        <div class="re-meta">
            <span>تاريخ التصدير: {{ $exported_at->format('Y-m-d H:i') }}</span>
        </div>
    </header>

    {{-- ── Summary ── --}}
    <section class="re-card">
        <h2>ملخص الميزانية</h2>
        <table class="re-table">
            <thead>
                <tr>
                    <th>البند</th>
                    <th>المبلغ</th>
                    <th>% من الأصول</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>إجمالي الأصول</strong></td>
                    <td>{{ number_format($total_assets, 2) }}</td>
                    <td>100%</td>
                </tr>
                <tr>
                    <td>إجمالي الالتزامات</td>
                    <td>{{ number_format($total_liabilities, 2) }}</td>
                    <td>{{ $total_assets > 0 ? round(($total_liabilities / $total_assets) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td>إجمالي حقوق الملكية</td>
                    <td>{{ number_format($total_equity, 2) }}</td>
                    <td>{{ $total_assets > 0 ? round(($total_equity / $total_assets) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td><strong>الالتزامات + حقوق الملكية</strong></td>
                    <td><strong>{{ number_format($total_liabilities_and_equity, 2) }}</strong></td>
                    <td>—</td>
                </tr>
                <tr>
                    <td colspan="3">
                        <strong>الحالة:</strong>
                        {{ $is_balanced ? '✅ الميزانية متوازنة' : '⚠️ الميزانية غير متوازنة — فرق ' . number_format($difference, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </section>

    {{-- ── Assets ── --}}
    @if($assets->isNotEmpty())
    <section class="re-card">
        <h2>الأصول</h2>
        <table class="re-table">
            <thead>
                <tr>
                    <th>الكود</th>
                    <th>اسم الحساب</th>
                    <th>المستوى</th>
                    <th>الرصيد</th>
                    <th>% من الأصول</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assets as $row)
                <tr @if($row->is_parent) style="font-weight:bold;background:#f0fdf4;" @endif>
                    <td>{{ $row->code }}</td>
                    <td>{{ str_repeat('　', $row->depth) }}{{ $row->name }}
                        @if($row->is_abnormal) (معكوس) @endif
                    </td>
                    <td>{{ $row->depth === 0 ? 'رئيسي' : 'فرعي' }}</td>
                    <td>{{ $row->is_abnormal ? '('.$fmt($row->net_balance).')' : $fmt($row->net_balance) }}</td>
                    <td>{{ $row->pct_of_assets }}%</td>
                </tr>
                @endforeach
                <tr style="font-weight:bold;border-top:2px solid #333;">
                    <td colspan="3">إجمالي الأصول</td>
                    <td>{{ number_format($total_assets, 2) }}</td>
                    <td>100%</td>
                </tr>
            </tbody>
        </table>
    </section>
    @endif

    {{-- ── Liabilities ── --}}
    @if($liabilities->isNotEmpty())
    <section class="re-card">
        <h2>الالتزامات</h2>
        <table class="re-table">
            <thead>
                <tr>
                    <th>الكود</th>
                    <th>اسم الحساب</th>
                    <th>المستوى</th>
                    <th>الرصيد</th>
                    <th>% من الأصول</th>
                </tr>
            </thead>
            <tbody>
                @foreach($liabilities as $row)
                <tr @if($row->is_parent) style="font-weight:bold;background:#fff5f5;" @endif>
                    <td>{{ $row->code }}</td>
                    <td>{{ str_repeat('　', $row->depth) }}{{ $row->name }}
                        @if($row->is_abnormal) (معكوس) @endif
                    </td>
                    <td>{{ $row->depth === 0 ? 'رئيسي' : 'فرعي' }}</td>
                    <td>{{ $row->is_abnormal ? '('.$fmt($row->net_balance).')' : $fmt($row->net_balance) }}</td>
                    <td>{{ $row->pct_of_assets }}%</td>
                </tr>
                @endforeach
                <tr style="font-weight:bold;border-top:2px solid #333;">
                    <td colspan="3">إجمالي الالتزامات</td>
                    <td>{{ number_format($total_liabilities, 2) }}</td>
                    <td>{{ $total_assets > 0 ? round(($total_liabilities / $total_assets) * 100, 1) : 0 }}%</td>
                </tr>
            </tbody>
        </table>
    </section>
    @endif

    {{-- ── Equity ── --}}
    @if($equity->isNotEmpty())
    <section class="re-card">
        <h2>حقوق الملكية</h2>
        <table class="re-table">
            <thead>
                <tr>
                    <th>الكود</th>
                    <th>اسم الحساب</th>
                    <th>المستوى</th>
                    <th>الرصيد</th>
                    <th>% من الأصول</th>
                </tr>
            </thead>
            <tbody>
                @foreach($equity as $row)
                <tr @if($row->is_parent) style="font-weight:bold;background:#fff7ed;" @endif>
                    <td>{{ $row->code }}</td>
                    <td>{{ str_repeat('　', $row->depth) }}{{ $row->name }}
                        @if($row->is_abnormal) (معكوس) @endif
                    </td>
                    <td>{{ $row->depth === 0 ? 'رئيسي' : 'فرعي' }}</td>
                    <td>{{ $row->is_abnormal ? '('.$fmt($row->net_balance).')' : $fmt($row->net_balance) }}</td>
                    <td>{{ $row->pct_of_assets }}%</td>
                </tr>
                @endforeach
                <tr style="font-weight:bold;border-top:2px solid #333;">
                    <td colspan="3">إجمالي حقوق الملكية</td>
                    <td>{{ number_format($total_equity, 2) }}</td>
                    <td>{{ $total_assets > 0 ? round(($total_equity / $total_assets) * 100, 1) : 0 }}%</td>
                </tr>
                <tr style="font-weight:bold;border-top:3px double #333;background:#f8fafc;">
                    <td colspan="3">إجمالي الالتزامات + حقوق الملكية</td>
                    <td>{{ number_format($total_liabilities_and_equity, 2) }}</td>
                    <td>—</td>
                </tr>
            </tbody>
        </table>
    </section>
    @endif

</div>
</body>
</html>

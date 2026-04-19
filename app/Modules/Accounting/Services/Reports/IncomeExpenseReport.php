<?php

namespace App\Modules\Accounting\Services\Reports;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IncomeExpenseReport
{
    /**
     * @return array{
     *   series: Collection,
     *   income_rows: Collection,
     *   expense_rows: Collection,
     *   total_income: float,
     *   total_expenses: float,
     *   net_result: float,
     *   average_income: float,
     *   average_expense: float,
     *   average_net: float,
     *   series_mode: string,
     *   insights: array,
     *   top_income_bucket: object|null,
     *   top_expense_bucket: object|null,
     * }
     */
    public function generate(int $companyId, string $from, string $to): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end   = Carbon::parse($to)->endOfDay();

        $dailyActivity = $this->dailyActivity($companyId, $from, $to);
        $seriesMode    = $this->seriesMode($start, $end);
        $series        = $this->collapseSeries($dailyActivity, $start, $end, $seriesMode);

        $incomeRows = $this->sumByType($companyId, 'revenue', $from, $to);
        $expenseRows = $this->sumByType($companyId, 'expense', $from, $to);

        $totalIncome   = (float) $incomeRows->sum('amount');
        $totalExpenses = (float) $expenseRows->sum('amount');
        $netResult     = $totalIncome - $totalExpenses;
        $daysCount     = max(1, $start->diffInDays($end) + 1);

        $incomeRows = $incomeRows->map(function ($row) use ($totalIncome) {
            $row->pct = $totalIncome > 0
                ? round(($row->amount / $totalIncome) * 100, 1)
                : 0.0;

            return $row;
        });

        $expenseRows = $expenseRows->map(function ($row) use ($totalExpenses) {
            $row->pct = $totalExpenses > 0
                ? round(($row->amount / $totalExpenses) * 100, 1)
                : 0.0;

            return $row;
        });

        return [
            'series'             => $series,
            'income_rows'        => $incomeRows,
            'expense_rows'       => $expenseRows,
            'total_income'       => $totalIncome,
            'total_expenses'     => $totalExpenses,
            'net_result'         => $netResult,
            'average_income'     => round($totalIncome / $daysCount, 2),
            'average_expense'    => round($totalExpenses / $daysCount, 2),
            'average_net'        => round($netResult / $daysCount, 2),
            'series_mode'        => $seriesMode,
            'insights'           => $this->buildInsights($series, $incomeRows, $expenseRows, $totalIncome, $totalExpenses, $netResult),
            'top_income_bucket'  => $series->sortByDesc('income')->first(),
            'top_expense_bucket' => $series->sortByDesc('expense')->first(),
        ];
    }

    private function dailyActivity(int $companyId, string $from, string $to): Collection
    {
        return DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status', 'posted')
            ->whereBetween('je.entry_date', [$from, $to])
            ->whereIn('a.type', ['revenue', 'expense'])
            ->groupBy('je.entry_date')
            ->orderBy('je.entry_date')
            ->select([
                'je.entry_date',
                DB::raw("SUM(CASE WHEN a.type = 'revenue' THEN jl.credit - jl.debit ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN a.type = 'expense' THEN jl.debit - jl.credit ELSE 0 END) as expense"),
            ])
            ->get()
            ->keyBy('entry_date');
    }

    private function sumByType(int $companyId, string $type, string $from, string $to): Collection
    {
        return DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status', 'posted')
            ->where('a.type', $type)
            ->whereBetween('je.entry_date', [$from, $to])
            ->groupBy('jl.account_id', 'a.code', 'a.name')
            ->orderByDesc(DB::raw($type === 'revenue'
                ? 'SUM(jl.credit) - SUM(jl.debit)'
                : 'SUM(jl.debit) - SUM(jl.credit)'))
            ->select([
                'jl.account_id',
                'a.code',
                'a.name',
                $type === 'revenue'
                    ? DB::raw('SUM(jl.credit) - SUM(jl.debit) as amount')
                    : DB::raw('SUM(jl.debit) - SUM(jl.credit) as amount'),
            ])
            ->get()
            ->filter(fn ($row) => (float) $row->amount > 0)
            ->values();
    }

    private function seriesMode(Carbon $start, Carbon $end): string
    {
        $days = $start->diffInDays($end) + 1;

        return match (true) {
            $days <= 45  => 'daily',
            $days <= 180 => 'weekly',
            default      => 'monthly',
        };
    }

    private function collapseSeries(Collection $dailyActivity, Carbon $start, Carbon $end, string $mode): Collection
    {
        $buckets = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $daily = $dailyActivity->get($date->toDateString());
            $bucketKey = $this->bucketKey($date, $mode);

            if (! isset($buckets[$bucketKey])) {
                $buckets[$bucketKey] = (object) [
                    'key'       => $bucketKey,
                    'label'     => $this->bucketLabel($date, $mode),
                    'range'     => $this->bucketRange($date, $mode),
                    'income'    => 0.0,
                    'expense'   => 0.0,
                    'net'       => 0.0,
                ];
            }

            $buckets[$bucketKey]->income += (float) ($daily->income ?? 0);
            $buckets[$bucketKey]->expense += (float) ($daily->expense ?? 0);
        }

        return collect(array_values($buckets))
            ->map(function (object $bucket): object {
                $bucket->income  = round($bucket->income, 2);
                $bucket->expense = round($bucket->expense, 2);
                $bucket->net     = round($bucket->income - $bucket->expense, 2);

                return $bucket;
            });
    }

    private function bucketKey(Carbon $date, string $mode): string
    {
        return match ($mode) {
            'daily'   => $date->toDateString(),
            'weekly'  => $date->copy()->startOfWeek(Carbon::SATURDAY)->toDateString(),
            'monthly' => $date->format('Y-m'),
        };
    }

    private function bucketLabel(Carbon $date, string $mode): string
    {
        return match ($mode) {
            'daily'   => $date->locale('ar')->translatedFormat('j M'),
            'weekly'  => 'أسبوع ' . $date->copy()->startOfWeek(Carbon::SATURDAY)->locale('ar')->translatedFormat('j M'),
            'monthly' => $date->locale('ar')->translatedFormat('F Y'),
        };
    }

    private function bucketRange(Carbon $date, string $mode): string
    {
        return match ($mode) {
            'daily' => $date->locale('ar')->translatedFormat('j F Y'),
            'weekly' => $date->copy()->startOfWeek(Carbon::SATURDAY)->locale('ar')->translatedFormat('j M')
                . ' - '
                . $date->copy()->endOfWeek(Carbon::FRIDAY)->locale('ar')->translatedFormat('j M'),
            'monthly' => $date->locale('ar')->translatedFormat('F Y'),
        };
    }

    private function buildInsights(
        Collection $series,
        Collection $incomeRows,
        Collection $expenseRows,
        float $totalIncome,
        float $totalExpenses,
        float $netResult,
    ): array {
        $insights = [];

        if ($totalIncome === 0.0 && $totalExpenses === 0.0) {
            return [[
                'level'      => 'info',
                'message'    => 'لا توجد حركات دخل أو مصروف في الفترة المحددة.',
                'suggestion' => 'ابدأ بتسجيل العمليات اليومية حتى تظهر لك حركة النشاط بشكل أوضح.',
            ]];
        }

        if ($totalIncome === 0.0) {
            $insights[] = [
                'level'      => 'warning',
                'message'    => 'الفترة الحالية تحتوي على مصروفات بدون أي دخل مسجل.',
                'suggestion' => 'راجع هل هناك إيرادات لم يتم ترحيلها أو فترة النشاط ضعيفة بالفعل.',
            ];
        }

        if ($netResult < 0) {
            $insights[] = [
                'level'      => 'error',
                'message'    => 'المصروفات أعلى من الدخل في هذه الفترة.',
                'suggestion' => 'ابدأ بأكبر بند مصروف لأنه غالبًا أسرع نقطة لتحسين النتيجة.',
            ];
        } elseif ($netResult > 0) {
            $insights[] = [
                'level'      => 'success',
                'message'    => 'الفترة الحالية رابحة وصافي النشاط إيجابي.',
                'suggestion' => 'استمر في متابعة البنود الأعلى دخلاً للحفاظ على نفس الزخم.',
            ];
        }

        $topExpense = $expenseRows->first();
        if ($topExpense && $topExpense->pct >= 40) {
            $insights[] = [
                'level'      => 'warning',
                'message'    => "بند {$topExpense->name} يستهلك {$topExpense->pct}% من إجمالي المصروفات.",
                'suggestion' => 'تخفيف هذا البند وحده قد ينعكس مباشرة على صافي الربح.',
            ];
        }

        $bestIncomeDay = $series->sortByDesc('income')->first();
        if ($bestIncomeDay && $bestIncomeDay->income > 0) {
            $insights[] = [
                'level'      => 'info',
                'message'    => "أفضل فترة دخل كانت {$bestIncomeDay->label} بقيمة " . number_format($bestIncomeDay->income, 2) . '.',
                'suggestion' => 'تكرار نفس نوع النشاط أو القناة قد يرفع متوسط الدخل للفترات القادمة.',
            ];
        }

        return $insights;
    }
}

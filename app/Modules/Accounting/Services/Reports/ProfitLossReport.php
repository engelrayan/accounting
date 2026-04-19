<?php

namespace App\Modules\Accounting\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfitLossReport
{
    /**
     * Generate a full Profit & Loss report.
     *
     * @return array{
     *   revenue_rows: Collection,
     *   expense_rows: Collection,
     *   total_revenue: float,
     *   total_expenses: float,
     *   net_profit: float,
     *   margin_pct: float,
     *   net_reason: string,
     *   from: string|null,
     *   to: string|null,
     *   has_comparison: bool,
     *   prev_from: string|null,
     *   prev_to: string|null,
     *   prev_revenue: float,
     *   prev_expenses: float,
     *   prev_net_profit: float,
     *   revenue_change_pct: float|null,
     *   expense_change_pct: float|null,
     *   profit_change_pct: float|null,
     *   insights: array,
     * }
     */
    public function generate(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $revenueRows = $this->sumByType($companyId, 'revenue', $from, $to);
        $expenseRows = $this->sumByType($companyId, 'expense', $from, $to);

        $totalRevenue  = (float) $revenueRows->sum('amount');
        $totalExpenses = (float) $expenseRows->sum('amount');
        $netProfit     = $totalRevenue - $totalExpenses;
        $totalActivity = $totalRevenue + $totalExpenses;

        $marginPct = $totalRevenue > 0
            ? round(($netProfit / $totalRevenue) * 100, 1)
            : 0.0;

        // ── Per-row enrichment ───────────────────────────────────────────────
        // Revenue: % of total revenue (how much of our revenue comes from this account)
        $revenueRows = $revenueRows->map(function ($row) use ($totalRevenue) {
            $row->pct             = $totalRevenue > 0
                ? round(($row->amount / $totalRevenue) * 100, 1)
                : 0.0;
            $row->pct_of_activity = 0.0; // not shown for revenue
            return $row;
        });

        // Expenses: % of total expenses + % of total activity
        $expenseRows = $expenseRows->map(function ($row) use ($totalExpenses, $totalActivity) {
            $row->pct             = $totalExpenses > 0
                ? round(($row->amount / $totalExpenses) * 100, 1)
                : 0.0;
            $row->pct_of_activity = $totalActivity > 0
                ? round(($row->amount / $totalActivity) * 100, 1)
                : 0.0;
            return $row;
        });

        // ── Previous period ──────────────────────────────────────────────────
        [$prevFrom, $prevTo] = $this->previousPeriod($from, $to);

        $prevRevenue   = 0.0;
        $prevExpenses  = 0.0;
        $prevNetProfit = 0.0;
        $hasComparison = false;

        if ($prevFrom || $prevTo) {
            $prevRevRows   = $this->sumByType($companyId, 'revenue', $prevFrom, $prevTo);
            $prevExpRows   = $this->sumByType($companyId, 'expense', $prevFrom, $prevTo);
            $prevRevenue   = (float) $prevRevRows->sum('amount');
            $prevExpenses  = (float) $prevExpRows->sum('amount');
            $prevNetProfit = $prevRevenue - $prevExpenses;
            $hasComparison = true;
        }

        $revenueChangePct = $prevRevenue  > 0
            ? round((($totalRevenue  - $prevRevenue)  / $prevRevenue)  * 100, 1)
            : null;

        $expenseChangePct = $prevExpenses > 0
            ? round((($totalExpenses - $prevExpenses) / $prevExpenses) * 100, 1)
            : null;

        $profitChangePct = $prevNetProfit != 0
            ? round((($netProfit - $prevNetProfit) / abs($prevNetProfit)) * 100, 1)
            : null;

        // ── Insights + net reason ────────────────────────────────────────────
        $insights  = $this->buildInsights(
            $totalRevenue, $totalExpenses, $netProfit,
            $marginPct, $revenueChangePct, $expenseChangePct,
            $expenseRows
        );

        $netReason = $this->buildNetReason(
            $totalRevenue, $totalExpenses, $netProfit, $marginPct
        );

        return [
            'revenue_rows'       => $revenueRows,
            'expense_rows'       => $expenseRows,
            'total_revenue'      => $totalRevenue,
            'total_expenses'     => $totalExpenses,
            'net_profit'         => $netProfit,
            'margin_pct'         => $marginPct,
            'net_reason'         => $netReason,
            'from'               => $from,
            'to'                 => $to,
            'has_comparison'     => $hasComparison,
            'prev_from'          => $prevFrom,
            'prev_to'            => $prevTo,
            'prev_revenue'       => $prevRevenue,
            'prev_expenses'      => $prevExpenses,
            'prev_net_profit'    => $prevNetProfit,
            'revenue_change_pct' => $revenueChangePct,
            'expense_change_pct' => $expenseChangePct,
            'profit_change_pct'  => $profitChangePct,
            'insights'           => $insights,
        ];
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function sumByType(
        int     $companyId,
        string  $type,
        ?string $from,
        ?string $to
    ): Collection {
        return DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a',         'a.id',  '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status',    'posted')
            ->where('a.type',       $type)
            ->when($from, fn ($q) => $q->where('je.entry_date', '>=', $from))
            ->when($to,   fn ($q) => $q->where('je.entry_date', '<=', $to))
            ->groupBy('jl.account_id', 'a.code', 'a.name')
            ->orderByDesc(DB::raw('SUM(jl.debit) - SUM(jl.credit)'))
            ->select([
                'jl.account_id',
                'a.code',
                'a.name',
                $type === 'revenue'
                    ? DB::raw('SUM(jl.credit) - SUM(jl.debit) as amount')
                    : DB::raw('SUM(jl.debit)  - SUM(jl.credit) as amount'),
            ])
            ->get()
            ->filter(fn ($r) => $r->amount > 0)
            ->values();
    }

    /**
     * Derive the "previous period" that mirrors the current one in length.
     */
    private function previousPeriod(?string $from, ?string $to): array
    {
        if (!$from && !$to) {
            return [null, null];
        }

        $now = Carbon::today();

        if ($from && $to) {
            $f    = Carbon::parse($from);
            $t    = Carbon::parse($to);
            $days = $f->diffInDays($t) + 1;
            return [
                $f->copy()->subDays($days)->toDateString(),
                $f->copy()->subDay()->toDateString(),
            ];
        }

        if ($from) {
            $f    = Carbon::parse($from);
            $days = $f->diffInDays($now) + 1;
            return [
                $f->copy()->subDays($days)->toDateString(),
                $f->copy()->subDay()->toDateString(),
            ];
        }

        $t = Carbon::parse($to);
        return [
            $t->copy()->subYear()->startOfYear()->toDateString(),
            $t->copy()->subYear()->toDateString(),
        ];
    }

    /**
     * Build a single human-readable sentence explaining the net result.
     */
    private function buildNetReason(
        float $totalRevenue,
        float $totalExpenses,
        float $netProfit,
        float $marginPct
    ): string {
        if ($totalRevenue === 0.0 && $totalExpenses === 0.0) {
            return 'لا توجد حركات مالية مُرحَّلة في هذه الفترة.';
        }

        if ($totalRevenue === 0.0) {
            return 'بسبب غياب الإيرادات — كل المصروفات المُسجَّلة تُشكِّل خسارة صافية.';
        }

        if ($netProfit < 0) {
            $excess = number_format($totalExpenses - $totalRevenue, 2);
            return "بسبب تجاوز المصروفات للإيرادات بفارق {$excess}.";
        }

        if ($marginPct >= 30) {
            return 'بفضل إيرادات قوية وسيطرة جيدة على التكاليف.';
        }

        if ($marginPct >= 15) {
            return 'الإيرادات تتجاوز المصروفات بهامش مقبول.';
        }

        return 'الإيرادات تغطي المصروفات بفارق ضئيل — راجع فرص تحسين الهامش.';
    }

    /**
     * Build rich insight objects: message + suggestion + optional CTA.
     *
     * Each insight shape:
     *   ['level' => 'error|warning|success|info',
     *    'message' => '...',
     *    'suggestion' => '...',        // optional
     *    'cta' => ['label'=>'...', 'route'=>'...', 'params'=>[]]]  // optional
     */
    private function buildInsights(
        float      $totalRevenue,
        float      $totalExpenses,
        float      $netProfit,
        float      $marginPct,
        ?float     $revenueChangePct,
        ?float     $expenseChangePct,
        Collection $expenseRows
    ): array {
        $insights = [];

        // ── No data at all ──────────────────────────────────────────────────
        if ($totalRevenue === 0.0 && $totalExpenses === 0.0) {
            $insights[] = [
                'level'      => 'info',
                'message'    => 'لا توجد بيانات مالية مُرحَّلة في هذه الفترة.',
                'suggestion' => 'سجِّل معاملاتك المالية وارحِّل القيود لتبدأ في رؤية تقارير دقيقة.',
                'cta'        => [
                    'label'  => 'إضافة معاملة',
                    'route'  => 'accounting.transactions.create',
                    'params' => [],
                ],
            ];
            return $insights;
        }

        // ── No revenue ──────────────────────────────────────────────────────
        if ($totalRevenue === 0.0) {
            $insights[] = [
                'level'      => 'warning',
                'message'    => 'لم يتم تسجيل أي إيرادات في هذه الفترة.',
                'suggestion' => 'ابدأ بإضافة إيرادات لتقليل الخسارة وتحسين الصورة المالية.',
                'cta'        => [
                    'label'  => 'إضافة إيراد',
                    'route'  => 'accounting.transactions.create',
                    'params' => [],
                ],
            ];
        }

        // ── Loss ────────────────────────────────────────────────────────────
        if ($netProfit < 0) {
            $excess = number_format(abs($netProfit), 2);
            $insights[] = [
                'level'      => 'error',
                'message'    => "الشركة في حالة خسارة بمقدار {$excess}.",
                'suggestion' => $totalRevenue === 0.0
                    ? 'أضف إيرادات لتغطية المصروفات المُسجَّلة.'
                    : 'راجع أكبر بنود المصروفات وحاول تخفيضها أو زيادة الإيرادات.',
            ];
        }

        // ── Low margin ──────────────────────────────────────────────────────
        if ($netProfit > 0 && $marginPct < 10) {
            $insights[] = [
                'level'      => 'warning',
                'message'    => "هامش الربح منخفض جداً ({$marginPct}%).",
                'suggestion' => 'حاول رفع الأسعار أو خفض المصروفات الثابتة لتحسين الهامش.',
            ];
        } elseif ($netProfit > 0 && $marginPct < 20) {
            $insights[] = [
                'level'      => 'warning',
                'message'    => "هامش الربح مقبول لكنه قابل للتحسين ({$marginPct}%).",
                'suggestion' => 'مراجعة بنود المصروفات قد ترفع الهامش إلى مستوى أفضل.',
            ];
        }

        // ── Excellent margin ────────────────────────────────────────────────
        if ($marginPct >= 30) {
            $insights[] = [
                'level'      => 'success',
                'message'    => "هامش ربح ممتاز ({$marginPct}%) — أداء مالي قوي.",
                'suggestion' => 'استمر في مراقبة التكاليف للحفاظ على هذا المستوى.',
            ];
        } elseif ($marginPct >= 20) {
            $insights[] = [
                'level'      => 'success',
                'message'    => "هامش ربح جيد ({$marginPct}%).",
                'suggestion' => null,
            ];
        }

        // ── Top expense is dominant ──────────────────────────────────────────
        $topExpense = $expenseRows->first();
        if ($topExpense && $topExpense->pct >= 50 && $totalExpenses > 0) {
            $insights[] = [
                'level'      => 'warning',
                'message'    => "حساب «{$topExpense->name}» يستهلك {$topExpense->pct}% من إجمالي المصروفات.",
                'suggestion' => 'تركُّز المصروفات في بند واحد يُشكِّل خطراً — ابحث عن بدائل أقل تكلفة.',
            ];
        }

        // ── Revenue declining vs previous period ────────────────────────────
        if ($revenueChangePct !== null && $revenueChangePct < -10) {
            $drop = abs($revenueChangePct);
            $insights[] = [
                'level'      => 'warning',
                'message'    => "تراجعت الإيرادات بنسبة {$drop}% مقارنةً بالفترة السابقة.",
                'suggestion' => 'تحقق من أسباب التراجع: هل توقفت مصادر إيراد معينة؟',
            ];
        }

        // ── Revenue growing ─────────────────────────────────────────────────
        if ($revenueChangePct !== null && $revenueChangePct > 10) {
            $insights[] = [
                'level'      => 'success',
                'message'    => "نمت الإيرادات بنسبة {$revenueChangePct}% مقارنةً بالفترة السابقة.",
                'suggestion' => null,
            ];
        }

        // ── Expenses growing fast ────────────────────────────────────────────
        if ($expenseChangePct !== null && $expenseChangePct > 20) {
            $insights[] = [
                'level'      => 'warning',
                'message'    => "ارتفعت المصروفات بنسبة {$expenseChangePct}% مقارنةً بالفترة السابقة.",
                'suggestion' => 'راجع بنود الإنفاق الجديدة وتأكد من ضرورتها.',
            ];
        }

        return $insights;
    }
}

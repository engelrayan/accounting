<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Modules\Accounting\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $companyId  = $request->user()->company_id;
        $today      = today()->toDateString();
        $weekStart  = today()->subDays(6)->toDateString();
        $monthStart = today()->startOfMonth()->toDateString();

        // ── 1. Cash balances (خزنة + بنك) — all-time posted ──────────────
        $cashRows = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status', 'posted')
            ->whereIn('a.code', ['1110', '1120'])
            ->selectRaw('a.code, a.name, SUM(jl.debit) - SUM(jl.credit) as balance')
            ->groupBy('a.code', 'a.name')
            ->get()
            ->keyBy('code');

        $cashBalance = (float) optional($cashRows->get('1110'))->balance;
        $bankBalance = (float) optional($cashRows->get('1120'))->balance;
        $totalCash   = $cashBalance + $bankBalance;

        // ── 2. Period metrics ──────────────────────────────────────────────
        // Expense (debit – credit on expense accounts)
        $expenseToday = $this->sumByType($companyId, 'expense', 'debit', $today, $today);
        $expenseWeek  = $this->sumByType($companyId, 'expense', 'debit', $weekStart, $today);
        $expenseMonth = $this->sumByType($companyId, 'expense', 'debit', $monthStart, $today);

        // Revenue (credit – debit on revenue accounts)
        $revenueToday = $this->sumByType($companyId, 'revenue', 'credit', $today, $today);
        $revenueWeek  = $this->sumByType($companyId, 'revenue', 'credit', $weekStart, $today);
        $revenueMonth = $this->sumByType($companyId, 'revenue', 'credit', $monthStart, $today);

        // ── 3. Profit ──────────────────────────────────────────────────────
        $profitMonth = $revenueMonth - $expenseMonth;
        $profitWeek  = $revenueWeek  - $expenseWeek;

        // ── 4. Monthly reports — last 12 months ───────────────────────────
        $since = today()->subMonths(11)->startOfMonth();

        $monthlyExpenses = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status', 'posted')
            ->where('a.type', 'expense')
            ->whereDate('je.entry_date', '>=', $since)
            ->selectRaw("DATE_FORMAT(je.entry_date, '%Y-%m') as period,
                         SUM(jl.debit) - SUM(jl.credit) as total")
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->get();

        $monthlyRevenues = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status', 'posted')
            ->where('a.type', 'revenue')
            ->whereDate('je.entry_date', '>=', $since)
            ->selectRaw("DATE_FORMAT(je.entry_date, '%Y-%m') as period,
                         SUM(jl.credit) - SUM(jl.debit) as total")
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->get();

        // ── 5. AR / AP outstanding balances ───────────────────────────────
        $arOutstanding = (float) DB::table('invoices')
            ->where('company_id', $companyId)
            ->whereIn('status', ['pending', 'partial'])
            ->sum('remaining_amount');

        $apOutstanding = (float) DB::table('purchase_invoices')
            ->where('company_id', $companyId)
            ->whereIn('status', ['pending', 'partial'])
            ->sum('remaining_amount');

        // ── 6. Expense breakdown this month (top 6 accounts) ──────────────
        $expenseBreakdown = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status', 'posted')
            ->where('a.type', 'expense')
            ->whereDate('je.entry_date', '>=', $monthStart)
            ->whereDate('je.entry_date', '<=', $today)
            ->selectRaw('a.name, SUM(jl.debit) - SUM(jl.credit) as total')
            ->groupBy('a.id', 'a.name')
            ->having('total', '>', 0)
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        // ── 7. Weekly data — filtered by selected month ───────────────────
        $arabicMonthsShort = [
            '01'=>'يناير','02'=>'فبراير','03'=>'مارس','04'=>'أبريل',
            '05'=>'مايو','06'=>'يونيو','07'=>'يوليو','08'=>'أغسطس',
            '09'=>'سبتمبر','10'=>'أكتوبر','11'=>'نوفمبر','12'=>'ديسمبر',
        ];

        // Read & validate ?week_month=YYYY-MM (default: current month)
        $weekMonth = $request->query('week_month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $weekMonth)) {
            $weekMonth = now()->format('Y-m');
        }

        $monthCarbon  = \Carbon\Carbon::createFromFormat('Y-m', $weekMonth)->startOfMonth();
        $monthEnd     = $monthCarbon->copy()->endOfMonth();
        $weeksFrom    = $monthCarbon->copy()->startOfWeek(); // Monday on/before month start
        $weeksTo      = min($monthEnd->toDateString(), $today); // don't exceed today

        // All Monday dates that intersect the selected month
        $weeksList = collect();
        $cursor    = $weeksFrom->copy();
        while ($cursor->lte($monthEnd)) {
            $weeksList->push($cursor->format('Y-m-d'));
            $cursor->addWeek();
        }

        $weeklyRevRaw = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status', 'posted')
            ->where('a.type', 'revenue')
            ->whereDate('je.entry_date', '>=', $weeksFrom)
            ->whereDate('je.entry_date', '<=', $weeksTo)
            ->selectRaw("DATE(je.entry_date - INTERVAL WEEKDAY(je.entry_date) DAY) as week_start,
                         SUM(jl.credit) - SUM(jl.debit) as total")
            ->groupBy('week_start')
            ->get()->keyBy('week_start');

        $weeklyExpRaw = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status', 'posted')
            ->where('a.type', 'expense')
            ->whereDate('je.entry_date', '>=', $weeksFrom)
            ->whereDate('je.entry_date', '<=', $weeksTo)
            ->selectRaw("DATE(je.entry_date - INTERVAL WEEKDAY(je.entry_date) DAY) as week_start,
                         SUM(jl.debit) - SUM(jl.credit) as total")
            ->groupBy('week_start')
            ->get()->keyBy('week_start');

        $weeklyChartData = [
            'labels'   => $weeksList->map(function ($w) use ($arabicMonthsShort, $monthEnd) {
                $start  = \Carbon\Carbon::parse($w);
                $end    = $start->copy()->endOfWeek()->min($monthEnd);
                $sMon   = $arabicMonthsShort[$start->format('m')] ?? '';
                $eMon   = $arabicMonthsShort[$end->format('m')]   ?? '';
                $label  = $start->day . ' ' . $sMon;
                if ($start->format('m') !== $end->format('m')) {
                    $label .= ' — ' . $end->day . ' ' . $eMon;
                } else {
                    $label .= ' — ' . $end->day;
                }
                return $label;
            })->values()->toArray(),
            'revenues' => $weeksList->map(fn ($w) => round(max(0, (float) ($weeklyRevRaw[$w]->total ?? 0)), 2))->values()->toArray(),
            'expenses' => $weeksList->map(fn ($w) => round(max(0, (float) ($weeklyExpRaw[$w]->total ?? 0)), 2))->values()->toArray(),
        ];

        // ── 8. Chart.js data — aligned 12-month arrays ────────────────────
        $arabicMonthsMap = [
            '01' => 'يناير',  '02' => 'فبراير', '03' => 'مارس',
            '04' => 'أبريل',  '05' => 'مايو',   '06' => 'يونيو',
            '07' => 'يوليو',  '08' => 'أغسطس',  '09' => 'سبتمبر',
            '10' => 'أكتوبر', '11' => 'نوفمبر', '12' => 'ديسمبر',
        ];

        $months  = collect(range(11, 0))->map(fn ($i) => today()->subMonths($i)->format('Y-m'));
        $revMap  = $monthlyRevenues->pluck('total', 'period');
        $expMap  = $monthlyExpenses->pluck('total', 'period');

        $chartData = [
            'labels'   => $months->map(fn ($m) => ($arabicMonthsMap[substr($m, 5)] ?? '') . ' ' . substr($m, 0, 4))->values()->toArray(),
            'revenues' => $months->map(fn ($m) => round(max(0, (float) ($revMap[$m] ?? 0)), 2))->values()->toArray(),
            'expenses' => $months->map(fn ($m) => round(max(0, (float) ($expMap[$m] ?? 0)), 2))->values()->toArray(),
            'profit'   => $months->map(fn ($m) => round((float) ($revMap[$m] ?? 0) - (float) ($expMap[$m] ?? 0), 2))->values()->toArray(),
        ];

        $breakdownData = [
            'labels' => $expenseBreakdown->pluck('name')->toArray(),
            'values' => $expenseBreakdown->map(fn ($r) => round((float) $r->total, 2))->toArray(),
        ];

        // ── 8. Recent transactions ─────────────────────────────────────────
        $recentTransactions = Transaction::forCompany($companyId)
            ->with(['fromAccount:id,name', 'toAccount:id,name'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('accounting.dashboard', compact(
            'cashBalance', 'bankBalance', 'totalCash',
            'expenseToday', 'expenseWeek', 'expenseMonth',
            'revenueToday', 'revenueWeek', 'revenueMonth',
            'profitMonth', 'profitWeek',
            'monthlyExpenses', 'monthlyRevenues',
            'arOutstanding', 'apOutstanding',
            'chartData', 'breakdownData', 'weeklyChartData', 'weekMonth',
            'recentTransactions',
        ));
    }

    // -------------------------------------------------------------------------

    /**
     * Sum debit or credit on a given account type, filtered by date range.
     * Returns max(0, net) to avoid showing negative metrics.
     *
     * @param string $side  'debit' for expenses, 'credit' for revenues
     */
    private function sumByType(
        int    $companyId,
        string $type,
        string $side,
        string $from,
        string $to,
    ): float {
        $row = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status', 'posted')
            ->where('a.type', $type)
            ->whereDate('je.entry_date', '>=', $from)
            ->whereDate('je.entry_date', '<=', $to)
            ->selectRaw('SUM(jl.debit) as d, SUM(jl.credit) as c')
            ->first();

        $d = (float) ($row->d ?? 0);
        $c = (float) ($row->c ?? 0);

        return max(0.0, $side === 'debit' ? $d - $c : $c - $d);
    }
}

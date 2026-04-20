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

        // ── 1. Cash balances (خزنة + بنك) ────────────────────────────────────
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

        // ── 2. Period metrics ─────────────────────────────────────────────────
        $expenseToday = $this->sumByType($companyId, 'expense', 'debit', $today, $today);
        $expenseWeek  = $this->sumByType($companyId, 'expense', 'debit', $weekStart, $today);
        $expenseMonth = $this->sumByType($companyId, 'expense', 'debit', $monthStart, $today);

        $revenueToday = $this->sumByType($companyId, 'revenue', 'credit', $today, $today);
        $revenueWeek  = $this->sumByType($companyId, 'revenue', 'credit', $weekStart, $today);
        $revenueMonth = $this->sumByType($companyId, 'revenue', 'credit', $monthStart, $today);

        // ── 3. Profit ─────────────────────────────────────────────────────────
        $profitMonth = $revenueMonth - $expenseMonth;
        $profitWeek  = $revenueWeek  - $expenseWeek;

        // ── 4. AR / AP outstanding ────────────────────────────────────────────
        $arOutstanding = (float) DB::table('invoices')
            ->where('company_id', $companyId)
            ->whereIn('status', ['pending', 'partial'])
            ->sum('remaining_amount');

        $apOutstanding = (float) DB::table('purchase_invoices')
            ->where('company_id', $companyId)
            ->whereIn('status', ['pending', 'partial'])
            ->sum('remaining_amount');

        // ── 5. Monthly chart data — last 12 months ────────────────────────────
        $since = today()->subMonths(11)->startOfMonth();

        $monthlyExpenses = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status', 'posted')
            ->where('a.type', 'expense')
            ->whereDate('je.entry_date', '>=', $since)
            ->selectRaw("DATE_FORMAT(je.entry_date,'%Y-%m') as period, SUM(jl.debit) - SUM(jl.credit) as total")
            ->groupBy('period')->orderBy('period', 'desc')->get();

        $monthlyRevenues = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status', 'posted')
            ->where('a.type', 'revenue')
            ->whereDate('je.entry_date', '>=', $since)
            ->selectRaw("DATE_FORMAT(je.entry_date,'%Y-%m') as period, SUM(jl.credit) - SUM(jl.debit) as total")
            ->groupBy('period')->orderBy('period', 'desc')->get();

        $arabicMonthsMap = [
            '01'=>'يناير','02'=>'فبراير','03'=>'مارس','04'=>'أبريل',
            '05'=>'مايو','06'=>'يونيو','07'=>'يوليو','08'=>'أغسطس',
            '09'=>'سبتمبر','10'=>'أكتوبر','11'=>'نوفمبر','12'=>'ديسمبر',
        ];

        $months = collect(range(11, 0))->map(fn ($i) => today()->subMonths($i)->format('Y-m'));
        $revMap = $monthlyRevenues->pluck('total', 'period');
        $expMap = $monthlyExpenses->pluck('total', 'period');

        $chartData = [
            'labels'   => $months->map(fn ($m) => ($arabicMonthsMap[substr($m, 5)] ?? '') . ' ' . substr($m, 0, 4))->values()->toArray(),
            'revenues' => $months->map(fn ($m) => round(max(0, (float) ($revMap[$m] ?? 0)), 2))->values()->toArray(),
            'expenses' => $months->map(fn ($m) => round(max(0, (float) ($expMap[$m] ?? 0)), 2))->values()->toArray(),
            'profit'   => $months->map(fn ($m) => round((float) ($revMap[$m] ?? 0) - (float) ($expMap[$m] ?? 0), 2))->values()->toArray(),
        ];

        // ── 6. Expense breakdown this month ───────────────────────────────────
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

        $breakdownData = [
            'labels' => $expenseBreakdown->pluck('name')->toArray(),
            'values' => $expenseBreakdown->map(fn ($r) => round((float) $r->total, 2))->toArray(),
        ];

        // ── 7. Weekly chart data ──────────────────────────────────────────────
        $arabicMonthsShort = $arabicMonthsMap;

        $weekMonth = $request->query('week_month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $weekMonth)) {
            $weekMonth = now()->format('Y-m');
        }

        $monthCarbon = \Carbon\Carbon::createFromFormat('Y-m', $weekMonth)->startOfMonth();
        $monthEnd    = $monthCarbon->copy()->endOfMonth();
        $weeksFrom   = $monthCarbon->copy()->startOfWeek();
        $weeksTo     = min($monthEnd->toDateString(), $today);

        $weeksList = collect();
        $cursor    = $weeksFrom->copy();
        while ($cursor->lte($monthEnd)) {
            $weeksList->push($cursor->format('Y-m-d'));
            $cursor->addWeek();
        }

        $weeklyRevRaw = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)->where('je.status', 'posted')
            ->where('a.type', 'revenue')
            ->whereDate('je.entry_date', '>=', $weeksFrom)->whereDate('je.entry_date', '<=', $weeksTo)
            ->selectRaw("DATE(je.entry_date - INTERVAL WEEKDAY(je.entry_date) DAY) as week_start, SUM(jl.credit) - SUM(jl.debit) as total")
            ->groupBy('week_start')->get()->keyBy('week_start');

        $weeklyExpRaw = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)->where('je.status', 'posted')
            ->where('a.type', 'expense')
            ->whereDate('je.entry_date', '>=', $weeksFrom)->whereDate('je.entry_date', '<=', $weeksTo)
            ->selectRaw("DATE(je.entry_date - INTERVAL WEEKDAY(je.entry_date) DAY) as week_start, SUM(jl.debit) - SUM(jl.credit) as total")
            ->groupBy('week_start')->get()->keyBy('week_start');

        $weeklyChartData = [
            'labels'   => $weeksList->map(function ($w) use ($arabicMonthsShort, $monthEnd) {
                $start = \Carbon\Carbon::parse($w);
                $end   = $start->copy()->endOfWeek()->min($monthEnd);
                $sMon  = $arabicMonthsShort[$start->format('m')] ?? '';
                $eMon  = $arabicMonthsShort[$end->format('m')]   ?? '';
                $label = $start->day . ' ' . $sMon;
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

        // ── 8. Recent transactions ────────────────────────────────────────────
        $recentTransactions = Transaction::forCompany($companyId)
            ->with(['fromAccount:id,name', 'toAccount:id,name'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // ── 9. Top 5 customers by outstanding ────────────────────────────────
        $topCustomers = DB::table('invoices as i')
            ->join('customers as c', 'c.id', '=', 'i.customer_id')
            ->where('i.company_id', $companyId)
            ->selectRaw('
                c.id,
                c.name,
                COUNT(i.id)                                  as invoice_count,
                SUM(i.amount)                                as total_invoiced,
                SUM(i.paid_amount)                           as total_paid,
                SUM(CASE WHEN i.status IN ("pending","partial") THEN i.remaining_amount ELSE 0 END) as outstanding
            ')
            ->groupBy('c.id', 'c.name')
            ->having('total_invoiced', '>', 0)
            ->orderByDesc('outstanding')
            ->limit(5)
            ->get();

        // ── 10. Alerts ────────────────────────────────────────────────────────
        $overdueCount = DB::table('invoices')
            ->where('company_id', $companyId)
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', '<', $today)
            ->whereNotNull('due_date')
            ->count();

        $overdueTotal = (float) DB::table('invoices')
            ->where('company_id', $companyId)
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', '<', $today)
            ->whereNotNull('due_date')
            ->sum('remaining_amount');

        $apOverdueCount = DB::table('purchase_invoices')
            ->where('company_id', $companyId)
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', '<', $today)
            ->whereNotNull('due_date')
            ->count();

        $alerts = [];

        if ($overdueCount > 0) {
            $alerts[] = [
                'type'    => 'danger',
                'icon'    => 'clock',
                'title'   => "فواتير متأخرة ({$overdueCount})",
                'message' => 'مستحق ' . number_format($overdueTotal, 2) . ' — تجاوزت تاريخ الاستحقاق',
                'link'    => route('accounting.reports.ar-aging'),
                'linkText'=> 'عرض التقرير',
            ];
        }

        if ($apOverdueCount > 0) {
            $alerts[] = [
                'type'    => 'warning',
                'icon'    => 'vendor',
                'title'   => "مستحقات موردين متأخرة ({$apOverdueCount})",
                'message' => 'فواتير شراء تجاوزت تاريخ السداد',
                'link'    => route('accounting.reports.ap-aging'),
                'linkText'=> 'عرض التقرير',
            ];
        }

        if ($totalCash < 1000 && $totalCash >= 0) {
            $alerts[] = [
                'type'    => 'warning',
                'icon'    => 'cash',
                'title'   => 'رصيد نقدي منخفض',
                'message' => 'إجمالي الخزنة والبنك: ' . number_format($totalCash, 2),
                'link'    => null,
                'linkText'=> null,
            ];
        }

        if ($arOutstanding > 50000) {
            $alerts[] = [
                'type'    => 'info',
                'icon'    => 'ar',
                'title'   => 'ذمم مدينة مرتفعة',
                'message' => number_format($arOutstanding, 2) . ' مستحق من العملاء',
                'link'    => route('accounting.customers.index', ['outstanding' => 1]),
                'linkText'=> 'عرض العملاء',
            ];
        }

        // ── 11. Recent activity log ───────────────────────────────────────────
        $recentActivity = DB::table('activity_logs as al')
            ->leftJoin('users as u', 'u.id', '=', 'al.user_id')
            ->where('al.company_id', $companyId)
            ->selectRaw('al.action, al.entity_type, al.entity_label, al.description, al.created_at, u.name as user_name')
            ->orderByDesc('al.created_at')
            ->limit(10)
            ->get();

        return view('accounting.dashboard', compact(
            // Cash
            'cashBalance', 'bankBalance', 'totalCash',
            // Period
            'expenseToday', 'expenseWeek', 'expenseMonth',
            'revenueToday', 'revenueWeek', 'revenueMonth',
            'profitMonth', 'profitWeek',
            // AR/AP
            'arOutstanding', 'apOutstanding',
            // Charts
            'chartData', 'breakdownData', 'weeklyChartData', 'weekMonth',
            // Transactions
            'recentTransactions',
            // New
            'topCustomers', 'alerts', 'recentActivity',
            'overdueCount', 'overdueTotal',
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────

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

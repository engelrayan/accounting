<?php

namespace App\Modules\Accounting\Services\Reports;

use Illuminate\Support\Facades\DB;

class TrialBalanceReport
{
    private const TYPE_CONFIG = [
        'asset'     => ['label' => 'الأصول',        'color' => 'blue'],
        'liability' => ['label' => 'الالتزامات',    'color' => 'red'],
        'equity'    => ['label' => 'حقوق الملكية', 'color' => 'amber'],
        'revenue'   => ['label' => 'الإيرادات',     'color' => 'green'],
        'expense'   => ['label' => 'المصروفات',     'color' => 'purple'],
    ];

    private const TYPE_ORDER = ['asset', 'liability', 'equity', 'revenue', 'expense'];

    public function generate(
        int     $companyId,
        ?string $from       = null,
        ?string $to         = null,
        ?string $filterType = null,
    ): array {
        // Single aggregation query — all accounts with any posted activity
        $rows = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status', 'posted')
            ->when($from,       fn ($q) => $q->where('je.entry_date', '>=', $from))
            ->when($to,         fn ($q) => $q->where('je.entry_date', '<=', $to))
            ->when($filterType, fn ($q) => $q->where('a.type', $filterType))
            ->groupBy('jl.account_id', 'a.code', 'a.name', 'a.type', 'a.normal_balance', 'a.is_active')
            ->orderBy('a.code')
            ->select([
                'jl.account_id',
                'a.code',
                'a.name',
                'a.type',
                'a.normal_balance',
                'a.is_active',
                DB::raw('SUM(jl.debit)  as total_debit'),
                DB::raw('SUM(jl.credit) as total_credit'),
            ])
            ->get()
            ->map(function ($row) {
                $d = (float) $row->total_debit;
                $c = (float) $row->total_credit;
                // Net balance in the account's natural direction
                $row->net_balance = $row->normal_balance === 'debit' ? $d - $c : $c - $d;
                // True when the balance is in the wrong direction
                $row->is_abnormal = $row->net_balance < 0;
                return $row;
            });

        // Build groups in display order, skip types with no data
        $groups = collect(self::TYPE_ORDER)
            ->mapWithKeys(function (string $type) use ($rows): array {
                $typeRows = $rows->where('type', $type)->values();
                if ($typeRows->isEmpty()) {
                    return [$type => null];
                }
                $cfg = self::TYPE_CONFIG[$type];
                return [$type => [
                    'label'        => $cfg['label'],
                    'color'        => $cfg['color'],
                    'accounts'     => $typeRows,
                    'total_debit'  => (float) $typeRows->sum('total_debit'),
                    'total_credit' => (float) $typeRows->sum('total_credit'),
                    'net'          => (float) $typeRows->sum('net_balance'),
                ]];
            })
            ->filter();

        $totalDebit   = (float) $rows->sum('total_debit');
        $totalCredit  = (float) $rows->sum('total_credit');
        $difference   = abs($totalDebit - $totalCredit);
        $isBalanced   = bccomp((string) $totalDebit, (string) $totalCredit, 2) === 0;
        $totalRevenue = (float) $rows->where('type', 'revenue')->sum('net_balance');
        $totalExpense = (float) $rows->where('type', 'expense')->sum('net_balance');

        return [
            'groups'        => $groups,
            'total_debit'   => $totalDebit,
            'total_credit'  => $totalCredit,
            'difference'    => $difference,
            'is_balanced'   => $isBalanced,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'insights'      => $this->buildInsights($isBalanced, $totalDebit, $totalCredit, $totalRevenue, $totalExpense),
            'from'          => $from,
            'to'            => $to,
            'filter_type'   => $filterType,
        ];
    }

    // -------------------------------------------------------------------------

    private function buildInsights(
        bool  $isBalanced,
        float $totalDebit,
        float $totalCredit,
        float $totalRevenue,
        float $totalExpense,
    ): array {
        $list = [];

        if (!$isBalanced) {
            $list[] = [
                'level'   => 'error',
                'message' => sprintf(
                    'النظام غير متوازن — مجموع المدين (%s) لا يساوي مجموع الدائن (%s).',
                    number_format($totalDebit, 2),
                    number_format($totalCredit, 2),
                ),
            ];
        }

        if ($totalRevenue === 0.0 && $totalExpense > 0.0) {
            $list[] = [
                'level'   => 'warning',
                'message' => 'لم تُسجَّل أي إيرادات في الفترة المحددة بينما توجد مصروفات.',
            ];
        } elseif ($totalExpense > 0.0 && $totalRevenue > 0.0 && $totalExpense > $totalRevenue) {
            $list[] = [
                'level'   => 'warning',
                'message' => sprintf(
                    'المصروفات تتجاوز الإيرادات بمقدار %s — راجع نفقات الشركة.',
                    number_format($totalExpense - $totalRevenue, 2),
                ),
            ];
        } elseif ($isBalanced && $totalRevenue > 0.0 && $totalRevenue >= $totalExpense) {
            $list[] = [
                'level'   => 'success',
                'message' => sprintf(
                    'الشركة في وضع ربحي — صافي الربح %s للفترة المحددة.',
                    number_format($totalRevenue - $totalExpense, 2),
                ),
            ];
        }

        return $list;
    }
}

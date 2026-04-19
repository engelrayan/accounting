<?php

namespace App\Modules\Accounting\Services\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BalanceSheetReport
{
    // =========================================================================
    // Generate
    // =========================================================================

    /**
     * Generate a Balance Sheet as of a given date.
     *
     * Returns a hierarchical, flattened account list for each section so the
     * view can render parent → child indentation with expand/collapse toggling.
     *
     * Each account row carries:
     *   - depth          : 0 = root, 1 = child, 2 = grandchild …
     *   - is_parent      : true when the account has children in the chart
     *   - own_balance    : balance from this account's own journal lines
     *   - net_balance    : own_balance + sum of all descendant balances
     *   - pct_of_section : net_balance / section_total × 100
     *   - pct_of_assets  : net_balance / total_assets  × 100
     *   - is_abnormal    : true when net_balance < 0 (contra-normal direction)
     *
     * Accounting equation verified: Assets = Liabilities + Equity
     *
     * @return array{
     *   assets:                       Collection,
     *   liabilities:                  Collection,
     *   equity:                       Collection,
     *   total_assets:                 float,
     *   total_liabilities:            float,
     *   total_equity:                 float,
     *   total_liabilities_and_equity: float,
     *   is_balanced:                  bool,
     *   difference:                   float,
     *   as_of:                        string,
     *   insights:                     array,
     * }
     */
    public function generate(int $companyId, ?string $asOf = null): array
    {
        $asOf = $asOf ?? today()->toDateString();

        // ── Step 1: Sum posted journal lines per account (up to asOf) ────────
        $balanceMap = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a',         'a.id',  '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status',    'posted')
            ->where('je.entry_date', '<=', $asOf)
            ->whereIn('a.type', ['asset', 'liability', 'equity'])
            ->groupBy('jl.account_id')
            ->select([
                'jl.account_id as id',
                DB::raw('SUM(jl.debit)  as total_debit'),
                DB::raw('SUM(jl.credit) as total_credit'),
            ])
            ->get()
            ->keyBy('id');

        // ── Step 2: Load every active balance-sheet account (chart) ──────────
        $accounts = DB::table('accounts')
            ->where('tenant_id', $companyId)
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->keyBy('id');

        // Attach own_balance from journal map; initialise tree fields
        $accounts->each(function ($account) use ($balanceMap) {
            $bal = $balanceMap->get($account->id);
            $d   = $bal ? (float) $bal->total_debit  : 0.0;
            $c   = $bal ? (float) $bal->total_credit : 0.0;

            $account->own_balance = $account->normal_balance === 'debit' ? $d - $c : $c - $d;
            $account->net_balance = $account->own_balance;
            $account->_children   = [];           // populated in step 3
        });

        // ── Step 3: Wire parent → children ───────────────────────────────────
        $roots = [];
        foreach ($accounts as $id => $account) {
            $pid = $account->parent_id;
            if ($pid && $accounts->has($pid)) {
                $accounts->get($pid)->_children[] = $account;
            } else {
                $roots[] = $account;
            }
        }

        // ── Step 4: Roll up net_balance bottom-up ────────────────────────────
        foreach ($roots as $root) {
            $this->computeNetBalance($root);
        }

        // ── Step 5: Flatten (DFS) and drop zero-balance accounts ─────────────
        $flatArr = [];
        foreach ($roots as $root) {
            $this->flattenNode($root, $flatArr, 0);
        }

        $flat = collect($flatArr)->filter(fn ($r) => abs($r->net_balance) > 0.001);

        // ── Step 6: Section totals — sum ONLY depth-0 items (no double-count) ─
        $totalAssets      = (float) $flat->where('type', 'asset')    ->where('depth', 0)->sum('net_balance');
        $totalLiabilities = (float) $flat->where('type', 'liability')->where('depth', 0)->sum('net_balance');
        $totalEquity      = (float) $flat->where('type', 'equity')   ->where('depth', 0)->sum('net_balance');

        // ── Step 7: Enrich each row with percentage columns ──────────────────
        $flat = $flat->map(function ($row) use ($totalAssets, $totalLiabilities, $totalEquity) {
            $sectionTotal = match ($row->type) {
                'asset'     => $totalAssets,
                'liability' => $totalLiabilities,
                'equity'    => $totalEquity,
                default     => 0.0,
            };

            $row->pct_of_section = $sectionTotal > 0
                ? round(($row->net_balance / $sectionTotal) * 100, 1) : 0.0;

            $row->pct_of_assets = $totalAssets > 0
                ? round(($row->net_balance / $totalAssets) * 100, 1) : 0.0;

            $row->is_abnormal = $row->net_balance < 0;

            return $row;
        });

        // ── Step 8: Split by type ─────────────────────────────────────────────
        $assets      = $flat->where('type', 'asset')    ->values();
        $liabilities = $flat->where('type', 'liability')->values();
        $equity      = $flat->where('type', 'equity')   ->values();

        // ── Step 9: Equation check ────────────────────────────────────────────
        $totalLE    = $totalLiabilities + $totalEquity;
        $difference = abs($totalAssets - $totalLE);
        $isBalanced = bccomp(
            number_format($totalAssets, 2, '.', ''),
            number_format($totalLE,     2, '.', ''),
            2
        ) === 0;

        return [
            'assets'                       => $assets,
            'liabilities'                  => $liabilities,
            'equity'                       => $equity,
            'total_assets'                 => $totalAssets,
            'total_liabilities'            => $totalLiabilities,
            'total_equity'                 => $totalEquity,
            'total_liabilities_and_equity' => $totalLE,
            'is_balanced'                  => $isBalanced,
            'difference'                   => $difference,
            'as_of'                        => $asOf,
            'insights'                     => $this->buildInsights(
                $isBalanced,
                $totalAssets,
                $totalLiabilities,
                $totalEquity,
                $difference,
                $assets,
                $liabilities,
            ),
        ];
    }

    // =========================================================================
    // Tree helpers
    // =========================================================================

    /**
     * Recursively sum children net_balance into each parent.
     * Also marks is_parent = true when a node has any children.
     */
    private function computeNetBalance(object $node): float
    {
        $childrenTotal = 0.0;

        foreach ($node->_children as $child) {
            $childrenTotal += $this->computeNetBalance($child);
        }

        $node->net_balance = $node->own_balance + $childrenTotal;
        $node->is_parent   = ! empty($node->_children);

        return $node->net_balance;
    }

    /**
     * Depth-first pre-order traversal.
     * Appends each node to $result with its computed depth.
     */
    private function flattenNode(object $node, array &$result, int $depth): void
    {
        $node->depth = $depth;
        $result[]    = $node;

        foreach ($node->_children as $child) {
            $this->flattenNode($child, $result, $depth + 1);
        }
    }

    // =========================================================================
    // Insights
    // =========================================================================

    /**
     * Build human-readable insight objects for the balance sheet.
     * Each: ['level' => error|warning|success|info, 'message' => '...', 'suggestion' => '...']
     */
    private function buildInsights(
        bool       $isBalanced,
        float      $totalAssets,
        float      $totalLiabilities,
        float      $totalEquity,
        float      $difference,
        Collection $assets,
        Collection $liabilities,
    ): array {
        $list = [];

        // ── No data ─────────────────────────────────────────────────────────
        if ($totalAssets === 0.0 && $totalLiabilities === 0.0 && $totalEquity === 0.0) {
            $list[] = [
                'level'      => 'info',
                'message'    => 'لا توجد بيانات مالية مُرحَّلة حتى هذا التاريخ.',
                'suggestion' => 'سجِّل القيود المحاسبية وارحِّلها لتظهر في الميزانية.',
            ];
            return $list;
        }

        // ── Equation balance ─────────────────────────────────────────────────
        if (! $isBalanced) {
            $list[] = [
                'level'      => 'error',
                'message'    => sprintf(
                    'الميزانية غير متوازنة — الفرق %s. الأصول لا تساوي (الالتزامات + حقوق الملكية).',
                    number_format($difference, 2)
                ),
                'suggestion' => 'راجع القيود المحاسبية غير المُرحَّلة أو الحسابات المُصنَّفة بشكل خاطئ.',
            ];
        } else {
            $list[] = [
                'level'   => 'success',
                'message' => 'الميزانية متوازنة — الأصول = الالتزامات + حقوق الملكية ✓',
            ];
        }

        // ── Negative equity ──────────────────────────────────────────────────
        if ($totalEquity < 0) {
            $list[] = [
                'level'      => 'error',
                'message'    => sprintf(
                    'حقوق الملكية سالبة (%s) — الشركة مدينة بأكثر مما تملك.',
                    number_format($totalEquity, 2)
                ),
                'suggestion' => 'خسائر متراكمة أو سحوبات مفرطة — راجع قائمة الأرباح والخسائر.',
            ];
        }

        // ── High leverage ────────────────────────────────────────────────────
        if ($totalEquity > 0 && $totalLiabilities > 2 * $totalEquity) {
            $ratio = round($totalLiabilities / $totalEquity, 1);
            $list[] = [
                'level'      => 'warning',
                'message'    => "نسبة الرافعة المالية مرتفعة — الالتزامات تعادل {$ratio}× حقوق الملكية.",
                'suggestion' => 'الاعتماد الكبير على الديون يزيد المخاطر المالية.',
            ];
        }

        // ── Dominant asset ───────────────────────────────────────────────────
        $topAsset = $assets->where('depth', 0)->sortByDesc('net_balance')->first();
        if ($topAsset && $totalAssets > 0 && ($topAsset->net_balance / $totalAssets) >= 0.70) {
            $list[] = [
                'level'      => 'warning',
                'message'    => sprintf(
                    'حساب «%s» يمثِّل %s%% من إجمالي الأصول — تركُّز مرتفع.',
                    $topAsset->name,
                    round(($topAsset->net_balance / $totalAssets) * 100, 1)
                ),
                'suggestion' => 'تنويع الأصول يُقلِّل المخاطر التشغيلية.',
            ];
        }

        // ── Healthy equity ratio ─────────────────────────────────────────────
        if ($totalAssets > 0 && $totalEquity > 0) {
            $equityRatio = round(($totalEquity / $totalAssets) * 100, 1);
            if ($equityRatio >= 50) {
                $list[] = [
                    'level'   => 'success',
                    'message' => "نسبة حقوق الملكية {$equityRatio}% من إجمالي الأصول — وضع مالي متين.",
                ];
            }
        }

        return $list;
    }
}

<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\Budget;
use App\Modules\Accounting\Models\BudgetLine;
use App\Modules\Accounting\Models\JournalLine;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    /**
     * Create a new draft budget for the given fiscal year.
     */
    public function create(int $companyId, string $name, int $year, ?string $notes = null): Budget
    {
        return Budget::create([
            'company_id'  => $companyId,
            'name'        => $name,
            'fiscal_year' => $year,
            'status'      => 'draft',
            'notes'       => $notes,
        ]);
    }

    /**
     * Upsert budget lines from the form submission.
     * $lines = [['account_id' => 1, 'month' => 1, 'amount' => 5000.00], ...]
     */
    public function saveLines(Budget $budget, array $lines): void
    {
        DB::transaction(function () use ($budget, $lines) {
            foreach ($lines as $line) {
                $amount = (float) ($line['amount'] ?? 0);
                if ($amount == 0) continue;

                BudgetLine::updateOrCreate(
                    [
                        'budget_id'    => $budget->id,
                        'account_id'   => (int) $line['account_id'],
                        'period_month' => (int) $line['month'],
                    ],
                    ['amount' => $amount]
                );
            }
        });
    }

    /**
     * Activate a draft budget.
     */
    public function activate(Budget $budget): Budget
    {
        $budget->update(['status' => 'active']);
        return $budget;
    }

    /**
     * Close an active budget.
     */
    public function close(Budget $budget): Budget
    {
        $budget->update(['status' => 'closed']);
        return $budget;
    }

    /**
     * Return budget vs actual comparison for a given budget.
     *
     * Returns:
     * [
     *   account_id => [
     *     'account'  => Account,
     *     'budget'   => [1..12 => amount],
     *     'actual'   => [1..12 => amount],
     *     'total_budget'  => float,
     *     'total_actual'  => float,
     *     'variance'      => float,
     *   ],
     *   ...
     * ]
     */
    public function getBudgetVsActual(Budget $budget): array
    {
        // Load budget lines with accounts
        $budgetLines = $budget->lines()->with('account')->get();

        if ($budgetLines->isEmpty()) {
            return [];
        }

        $accountIds = $budgetLines->pluck('account_id')->unique()->toArray();
        $year       = $budget->fiscal_year;

        // Pull actual posted amounts from journal entry lines per account per month
        $actuals = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->whereIn('journal_lines.account_id', $accountIds)
            ->where('journal_entries.tenant_id', $budget->company_id)
            ->where('journal_entries.status', 'posted')
            ->whereYear('journal_entries.entry_date', $year)
            ->selectRaw('
                journal_lines.account_id,
                MONTH(journal_entries.entry_date) as month,
                SUM(journal_lines.debit - journal_lines.credit) as net_amount
            ')
            ->groupBy('journal_lines.account_id', 'month')
            ->get()
            ->groupBy('account_id');

        // Build result matrix
        $result = [];

        foreach ($budgetLines->groupBy('account_id') as $accountId => $lines) {
            $account     = $lines->first()->account;
            $budgetByMonth = $lines->pluck('amount', 'period_month')->toArray();
            $actualByMonth = [];

            if (isset($actuals[$accountId])) {
                foreach ($actuals[$accountId] as $row) {
                    $actualByMonth[(int) $row->month] = abs((float) $row->net_amount);
                }
            }

            $totalBudget = array_sum($budgetByMonth);
            $totalActual = array_sum($actualByMonth);

            $result[$accountId] = [
                'account'      => $account,
                'budget'       => $budgetByMonth,
                'actual'       => $actualByMonth,
                'total_budget' => $totalBudget,
                'total_actual' => $totalActual,
                'variance'     => $totalBudget - $totalActual,
            ];
        }

        return $result;
    }
}

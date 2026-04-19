<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\FiscalYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FiscalYearCloseService
{
    public function __construct(
        private readonly JournalEntryService $journalEntryService,
    ) {}

    // -------------------------------------------------------------------------
    // Preview — read-only, no side effects
    // -------------------------------------------------------------------------

    /**
     * Return a P&L summary for the fiscal year without closing it.
     *
     * Returns:
     *   revenues      Collection of account rows
     *   expenses      Collection of account rows
     *   totalRevenue  float
     *   totalExpense  float
     *   netProfit     float  (positive = profit, negative = loss)
     */
    public function preview(FiscalYear $fiscalYear): array
    {
        $balances = $this->getAccountBalancesForPeriod(
            $fiscalYear->company_id,
            $fiscalYear->starts_at->toDateString(),
            $fiscalYear->ends_at->toDateString()
        );

        $revenues = $balances->where('type', 'revenue')->values();
        $expenses = $balances->where('type', 'expense')->values();

        $totalRevenue = $revenues->sum('balance');
        $totalExpense = $expenses->sum('balance');

        return [
            'revenues'     => $revenues,
            'expenses'     => $expenses,
            'totalRevenue' => $totalRevenue,
            'totalExpense' => $totalExpense,
            'netProfit'    => $totalRevenue - $totalExpense,
        ];
    }

    // -------------------------------------------------------------------------
    // Close — atomic, creates GL entry, locks the year
    // -------------------------------------------------------------------------

    /**
     * Close a fiscal year:
     *  1. Compute P&L from posted journal lines in the period.
     *  2. Build a balanced closing entry:
     *       DR each revenue account  (zero out credit balances)
     *       CR each expense account  (zero out debit balances)
     *       CR/DR retained earnings  (net profit or loss)
     *  3. Post the closing entry.
     *  4. Mark the fiscal year as closed.
     *
     * @throws \DomainException
     */
    public function close(FiscalYear $fiscalYear): array
    {
        if ($fiscalYear->isClosed()) {
            throw new \DomainException('هذه السنة المالية مغلقة بالفعل.');
        }

        $companyId = $fiscalYear->company_id;
        $startsAt  = $fiscalYear->starts_at->toDateString();
        $endsAt    = $fiscalYear->ends_at->toDateString();

        $balances = $this->getAccountBalancesForPeriod($companyId, $startsAt, $endsAt);

        $revenues = $balances->where('type', 'revenue');
        $expenses = $balances->where('type', 'expense');

        $totalRevenue = (float) $revenues->sum('balance');
        $totalExpense = (float) $expenses->sum('balance');
        $netProfit    = $totalRevenue - $totalExpense;

        DB::transaction(function () use (
            $fiscalYear, $companyId, $revenues, $expenses, $netProfit, $endsAt
        ) {
            $lines = [];

            // DR revenue accounts (normal balance = credit → DR to zero)
            foreach ($revenues as $acct) {
                if ($acct->balance > 0.001) {
                    $lines[] = [
                        'account_id'  => $acct->account_id,
                        'debit'       => round($acct->balance, 2),
                        'credit'      => 0,
                        'description' => 'قيد إقفال — ' . $acct->name,
                    ];
                } elseif ($acct->balance < -0.001) {
                    // Unusual: revenue with net debit balance (e.g. returns)
                    $lines[] = [
                        'account_id'  => $acct->account_id,
                        'debit'       => 0,
                        'credit'      => round(abs($acct->balance), 2),
                        'description' => 'قيد إقفال — ' . $acct->name,
                    ];
                }
            }

            // CR expense accounts (normal balance = debit → CR to zero)
            foreach ($expenses as $acct) {
                if ($acct->balance > 0.001) {
                    $lines[] = [
                        'account_id'  => $acct->account_id,
                        'debit'       => 0,
                        'credit'      => round($acct->balance, 2),
                        'description' => 'قيد إقفال — ' . $acct->name,
                    ];
                } elseif ($acct->balance < -0.001) {
                    // Unusual: expense with net credit balance
                    $lines[] = [
                        'account_id'  => $acct->account_id,
                        'debit'       => round(abs($acct->balance), 2),
                        'credit'      => 0,
                        'description' => 'قيد إقفال — ' . $acct->name,
                    ];
                }
            }

            // Retained earnings line (only if net ≠ 0)
            if (abs($netProfit) > 0.001) {
                $retained = $this->resolveRetainedEarningsAccount($companyId);
                if ($netProfit > 0) {
                    // Profit → CR retained earnings
                    $lines[] = [
                        'account_id'  => $retained->id,
                        'debit'       => 0,
                        'credit'      => round($netProfit, 2),
                        'description' => "ترحيل صافي الربح — إقفال سنة {$fiscalYear->year}",
                    ];
                } else {
                    // Loss → DR retained earnings
                    $lines[] = [
                        'account_id'  => $retained->id,
                        'debit'       => round(abs($netProfit), 2),
                        'credit'      => 0,
                        'description' => "ترحيل صافي الخسارة — إقفال سنة {$fiscalYear->year}",
                    ];
                }
            }

            $closingEntryId = null;

            if (! empty($lines)) {
                $entry = $this->journalEntryService->createEntry(
                    [
                        'company_id'     => $companyId,
                        'description'    => "قيد إقفال السنة المالية {$fiscalYear->year}",
                        'entry_date'     => $endsAt,
                        'reference_type' => 'fiscal_year',
                        'reference_id'   => $fiscalYear->id,
                    ],
                    $lines
                );

                $this->journalEntryService->postEntry($entry);
                $closingEntryId = $entry->id;
            }

            $fiscalYear->update([
                'status'           => 'closed',
                'net_profit'       => round($netProfit, 2),
                'closing_entry_id' => $closingEntryId,
                'closed_at'        => now(),
                'closed_by'        => Auth::id(),
            ]);
        });

        return [
            'netProfit'    => $netProfit,
            'revenueCount' => $revenues->count(),
            'expenseCount' => $expenses->count(),
        ];
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Fetch all revenue and expense accounts with non-zero net balances
     * from posted journal entries within the given date range.
     */
    private function getAccountBalancesForPeriod(
        int    $companyId,
        string $startsAt,
        string $endsAt
    ): Collection {
        return DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status', 'posted')
            ->whereBetween('je.entry_date', [$startsAt, $endsAt])
            ->whereIn('a.type', ['revenue', 'expense'])
            ->groupBy('jl.account_id', 'a.name', 'a.type', 'a.normal_balance')
            ->selectRaw('
                jl.account_id,
                a.name,
                a.type,
                a.normal_balance,
                SUM(jl.debit)  as total_debit,
                SUM(jl.credit) as total_credit
            ')
            ->get()
            ->map(function ($row) {
                // Balance in the direction of the account's normal balance
                $row->balance = $row->normal_balance === 'credit'
                    ? (float) $row->total_credit - (float) $row->total_debit   // revenue
                    : (float) $row->total_debit  - (float) $row->total_credit; // expense
                return $row;
            })
            ->filter(fn ($row) => abs($row->balance) > 0.001)
            ->values();
    }

    /**
     * Resolve the Retained Earnings equity account (code 3100).
     * Auto-creates it under the 3000 equity parent if missing.
     */
    private function resolveRetainedEarningsAccount(int $companyId): Account
    {
        $account = Account::where('tenant_id', $companyId)
            ->where('type', 'equity')
            ->where(function ($q) {
                $q->where('code', '3100')
                  ->orWhere('name', 'like', '%أرباح محتجزة%')
                  ->orWhere('name', 'like', '%أرباح مرحلة%')
                  ->orWhere('name', 'like', '%أرباح متراكمة%');
            })
            ->orderByRaw("CASE WHEN code = '3100' THEN 0 ELSE 1 END")
            ->first();

        if (! $account) {
            $parent = Account::where('tenant_id', $companyId)
                ->where('code', '3000')
                ->first();

            $account = Account::create([
                'tenant_id'      => $companyId,
                'parent_id'      => $parent?->id,
                'code'           => '3100',
                'name'           => 'أرباح محتجزة',
                'type'           => 'equity',
                'normal_balance' => 'credit',
                'is_system'      => false,
                'is_active'      => true,
            ]);
        }

        return $account;
    }
}

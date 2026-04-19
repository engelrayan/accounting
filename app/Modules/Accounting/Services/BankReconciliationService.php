<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\BankStatement;
use App\Modules\Accounting\Models\BankStatementLine;
use App\Modules\Accounting\Models\JournalLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BankReconciliationService
{
    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    /**
     * Start a new reconciliation session.
     * Creates the bank_statement header and persists all lines.
     */
    public function startReconciliation(
        int    $companyId,
        int    $accountId,
        string $statementDate,
        float  $openingBalance,
        float  $closingBalance,
        array  $lines,
    ): BankStatement {
        return DB::transaction(function () use ($companyId, $accountId, $statementDate, $openingBalance, $closingBalance, $lines) {
            $statement = BankStatement::create([
                'company_id'      => $companyId,
                'account_id'      => $accountId,
                'statement_date'  => $statementDate,
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance,
                'status'          => 'open',
            ]);

            foreach ($lines as $line) {
                $statement->lines()->create([
                    'transaction_date' => $line['transaction_date'],
                    'description'      => $line['description'],
                    'debit'            => (float) ($line['debit']  ?? 0),
                    'credit'           => (float) ($line['credit'] ?? 0),
                ]);
            }

            return $statement;
        });
    }

    // -------------------------------------------------------------------------
    // Matching
    // -------------------------------------------------------------------------

    /**
     * Link a bank statement line to a journal line (match pair).
     *
     * @throws \DomainException
     */
    public function matchLine(
        BankStatement $statement,
        int           $statementLineId,
        int           $journalLineId,
    ): void {
        if ($statement->isReconciled()) {
            throw new \DomainException('التسوية مغلقة ولا يمكن تعديلها.');
        }

        // Verify the bank statement line belongs to this statement
        $stmtLine = BankStatementLine::where('id', $statementLineId)
            ->where('bank_statement_id', $statement->id)
            ->firstOrFail();

        if ($stmtLine->is_matched) {
            throw new \DomainException('هذا السطر مطابق بالفعل. قم بإلغاء المطابقة أولاً.');
        }

        // Verify the journal line belongs to the correct account
        $journalLine = JournalLine::where('id', $journalLineId)
            ->where('account_id', $statement->account_id)
            ->firstOrFail();

        // Verify it is not already matched in another statement for this account
        $takenElsewhere = BankStatementLine::where('journal_line_id', $journalLineId)
            ->where('is_matched', true)
            ->where('bank_statement_id', '!=', $statement->id)
            ->exists();

        if ($takenElsewhere) {
            throw new \DomainException('هذا القيد مطابق بالفعل في تسوية بنكية أخرى.');
        }

        $stmtLine->update([
            'is_matched'      => true,
            'journal_line_id' => $journalLineId,
        ]);
    }

    /**
     * Remove the match from a bank statement line.
     *
     * @throws \DomainException
     */
    public function unmatchLine(BankStatement $statement, int $statementLineId): void
    {
        if ($statement->isReconciled()) {
            throw new \DomainException('التسوية مغلقة ولا يمكن تعديلها.');
        }

        $stmtLine = BankStatementLine::where('id', $statementLineId)
            ->where('bank_statement_id', $statement->id)
            ->firstOrFail();

        $stmtLine->update([
            'is_matched'      => false,
            'journal_line_id' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // Complete
    // -------------------------------------------------------------------------

    /**
     * Close the reconciliation. Requires difference = 0.
     *
     * @throws \DomainException
     */
    public function complete(BankStatement $statement): void
    {
        if ($statement->isReconciled()) {
            throw new \DomainException('التسوية مغلقة بالفعل.');
        }

        $summary = $this->getSummary($statement);

        if (! $summary['is_balanced']) {
            $diff = number_format(abs($summary['difference']), 2);
            throw new \DomainException(
                "لا يمكن إغلاق التسوية — الفرق الحالي {$diff} يجب أن يكون صفراً."
            );
        }

        $statement->update([
            'status'        => 'reconciled',
            'reconciled_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Summary / Reporting
    // -------------------------------------------------------------------------

    /**
     * Compute the reconciliation summary for the given statement.
     *
     * Returns:
     *   matched_count            — number of matched bank lines
     *   unmatched_statement_count — unmatched bank lines
     *   unmatched_ledger_count   — unmatched journal lines for this account
     *   unmatched_bank_net       — net of unmatched bank lines (credit − debit)
     *   unmatched_ledger_net     — net of unmatched ledger lines (credit − debit)
     *   difference               — unmatched_bank_net − unmatched_ledger_net
     *   is_balanced              — true when difference = 0
     */
    public function getSummary(BankStatement $statement): array
    {
        $statement->loadMissing('lines');

        $matched   = $statement->lines->where('is_matched', true);
        $unmatched = $statement->lines->where('is_matched', false);

        $unmatchedBankDebit  = (string) $unmatched->sum('debit');
        $unmatchedBankCredit = (string) $unmatched->sum('credit');
        $unmatchedBankNet    = bcsub($unmatchedBankCredit, $unmatchedBankDebit, 2);

        $unmatchedJournalLines  = $this->getUnmatchedJournalLines($statement);
        $unmatchedLedgerDebit   = (string) $unmatchedJournalLines->sum('debit');
        $unmatchedLedgerCredit  = (string) $unmatchedJournalLines->sum('credit');
        $unmatchedLedgerNet     = bcsub($unmatchedLedgerCredit, $unmatchedLedgerDebit, 2);

        $difference = bcsub($unmatchedBankNet, $unmatchedLedgerNet, 2);

        return [
            'matched_count'             => $matched->count(),
            'unmatched_statement_count' => $unmatched->count(),
            'unmatched_ledger_count'    => $unmatchedJournalLines->count(),
            'unmatched_bank_net'        => (float) $unmatchedBankNet,
            'unmatched_ledger_net'      => (float) $unmatchedLedgerNet,
            'difference'                => (float) $difference,
            'is_balanced'               => bccomp($difference, '0', 2) === 0,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * All journal lines for this account that are NOT matched in any other statement.
     * Lines already matched to THIS statement are also excluded (they show in the
     * bank-lines column, not the ledger column).
     */
    public function getUnmatchedJournalLines(BankStatement $statement): Collection
    {
        // Journal lines already matched anywhere (other statements + this one)
        $excludeIds = BankStatementLine::whereNotNull('journal_line_id')
            ->where('is_matched', true)
            ->pluck('journal_line_id');

        return JournalLine::where('account_id', $statement->account_id)
            ->whereNotIn('id', $excludeIds)
            ->whereHas('entry', function ($q) use ($statement) {
                $q->where('tenant_id', $statement->company_id)
                  ->where('status', 'posted');
            })
            ->with('entry:id,entry_number,entry_date,description')
            ->orderByDesc('id')
            ->get();
    }
}

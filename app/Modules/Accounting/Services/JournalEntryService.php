<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Exceptions\InvalidJournalEntryException;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\FiscalYear;
use App\Modules\Accounting\Models\JournalEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JournalEntryService
{
    // -------------------------------------------------------------------------
    // Create — always produces a DRAFT
    // -------------------------------------------------------------------------

    /**
     * Create a balanced journal entry as a draft.
     * Call postEntry() separately to move it to posted status.
     *
     * $data keys:
     *   - company_id      (int, required)   — maps to tenant_id column
     *   - description     (string, required)
     *   - entry_date      (string, required) — YYYY-MM-DD
     *   - reference_type  (string, nullable)
     *   - reference_id    (int, nullable)
     *
     * $lines is an array of:
     *   - account_id     (int, required)
     *   - debit          (numeric, default 0)
     *   - credit         (numeric, default 0)
     *   - description    (string, optional)
     *   - currency       (string, optional, default SAR)
     *   - exchange_rate  (numeric, optional, default 1)
     *
     * @throws InvalidJournalEntryException
     */
    public function createEntry(array $data, array $lines): JournalEntry
    {
        $companyId = $data['company_id'];

        // Block entries whose date falls within a closed fiscal year
        if (FiscalYear::isDateLocked($companyId, $data['entry_date'])) {
            throw new \DomainException(
                'لا يمكن إضافة قيود على فترة مالية مغلقة. ' .
                'تاريخ القيد يقع ضمن سنة مالية مُقفَلة.'
            );
        }

        $this->validateLines($companyId, $lines);

        return DB::transaction(function () use ($data, $lines, $companyId) {
            $entry = JournalEntry::create([
                'tenant_id'      => $companyId,
                'entry_number'   => $this->generateEntryNumber($companyId),
                'description'    => $data['description'],
                'entry_date'     => $data['entry_date'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id'   => $data['reference_id']   ?? null,
                'status'         => 'draft',
                'created_by'     => Auth::id(),
                'auto_generated' => true,
            ]);

            $entry->lines()->createMany(
                array_map(fn (array $line) => [
                    'account_id'    => $line['account_id'],
                    'debit'         => $line['debit']         ?? 0,
                    'credit'        => $line['credit']        ?? 0,
                    'description'   => $line['description']   ?? null,
                    'currency'      => $line['currency']      ?? 'SAR',
                    'exchange_rate' => $line['exchange_rate'] ?? 1,
                ], $lines)
            );

            return $entry->load('lines');
        });
    }

    // -------------------------------------------------------------------------
    // Post
    // -------------------------------------------------------------------------

    /**
     * Validate and mark a draft entry as posted.
     *
     * Guards:
     *  - Entry must be in draft status
     *  - Must have at least 2 lines
     *  - Total debit must equal total credit
     *
     * @throws InvalidJournalEntryException
     */
    public function postEntry(JournalEntry $entry): JournalEntry
    {
        if (! $entry->isDraft()) {
            throw InvalidJournalEntryException::notDraft($entry->entry_number);
        }

        $entry->loadMissing('lines');

        if ($entry->lines->count() < 2) {
            throw InvalidJournalEntryException::tooFewLines($entry->lines->count());
        }

        if (! $entry->isBalanced()) {
            $totals = $entry->lines()
                ->selectRaw('SUM(debit) as d, SUM(credit) as c')
                ->first();

            throw InvalidJournalEntryException::unbalanced(
                (string) $totals->d,
                (string) $totals->c
            );
        }

        $entry->update([
            'status'    => 'posted',
            'posted_at' => now(),
        ]);

        return $entry->refresh();
    }

    // -------------------------------------------------------------------------
    // Reverse
    // -------------------------------------------------------------------------

    /**
     * Create a reversal entry for a posted journal entry.
     *
     * The reversal:
     *  - Swaps debit and credit on every line
     *  - Is immediately posted
     *  - Carries reversal_of_id pointing back to the original
     *
     * The original entry is marked as "reversed" and its reversed_by
     * column is set to the new reversal entry's ID.
     *
     * Guards:
     *  - Original must be posted (not draft, not already reversed)
     *
     * @throws InvalidJournalEntryException
     */
    public function reverseEntry(JournalEntry $original, int $companyId): JournalEntry
    {
        if (! $original->isPosted()) {
            throw InvalidJournalEntryException::notPostable($original->entry_number);
        }

        if ($original->isReversed()) {
            throw InvalidJournalEntryException::alreadyReversed($original->entry_number);
        }

        $original->loadMissing('lines');

        return DB::transaction(function () use ($original, $companyId) {
            // Build reversed lines — swap debit ↔ credit
            $reversedLines = $original->lines->map(fn ($line) => [
                'account_id'    => $line->account_id,
                'debit'         => $line->credit,       // swapped
                'credit'        => $line->debit,        // swapped
                'description'   => $line->description,
                'currency'      => $line->currency,
                'exchange_rate' => $line->exchange_rate,
            ])->all();

            $reversal = JournalEntry::create([
                'tenant_id'      => $companyId,
                'entry_number'   => $this->generateEntryNumber($companyId),
                'description'    => "Reversal of {$original->entry_number}: {$original->description}",
                'entry_date'     => today()->toDateString(),
                'reference_type' => $original->reference_type,
                'reference_id'   => $original->reference_id,
                'reversal_of_id' => $original->id,
                'status'         => 'posted',
                'created_by'     => Auth::id(),
                'posted_at'      => now(),
            ]);

            $reversal->lines()->createMany($reversedLines);

            // Lock the original
            $original->update([
                'status'      => 'reversed',
                'reversed_by' => $reversal->id,
            ]);

            return $reversal->load('lines');
        });
    }

    // -------------------------------------------------------------------------
    // Business-level shortcuts
    // Each creates a draft then immediately posts it.
    // -------------------------------------------------------------------------

    /** DR Expense  CR Cash/Bank */
    public function recordExpense(
        int $companyId, int $expenseAccountId, int $cashAccountId,
        float $amount, string $description, string $date = null,
    ): JournalEntry {
        $entry = $this->createEntry(
            ['company_id' => $companyId, 'description' => $description, 'entry_date' => $date ?? today()->toDateString()],
            [
                ['account_id' => $expenseAccountId, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $cashAccountId,    'debit' => 0,       'credit' => $amount],
            ]
        );

        return $this->postEntry($entry);
    }

    /** DR Cash/Bank  CR Revenue */
    public function recordIncome(
        int $companyId, int $cashAccountId, int $revenueAccountId,
        float $amount, string $description, string $date = null,
    ): JournalEntry {
        $entry = $this->createEntry(
            ['company_id' => $companyId, 'description' => $description, 'entry_date' => $date ?? today()->toDateString()],
            [
                ['account_id' => $cashAccountId,    'debit' => $amount, 'credit' => 0],
                ['account_id' => $revenueAccountId, 'debit' => 0,       'credit' => $amount],
            ]
        );

        return $this->postEntry($entry);
    }

    /** DR AR  CR Revenue */
    public function recordInvoice(
        int $companyId, int $arAccountId, int $revenueAccountId,
        float $amount, int $referenceId, string $date = null,
    ): JournalEntry {
        $entry = $this->createEntry(
            ['company_id' => $companyId, 'description' => "Invoice #{$referenceId}", 'entry_date' => $date ?? today()->toDateString(), 'reference_type' => 'invoice', 'reference_id' => $referenceId],
            [
                ['account_id' => $arAccountId,      'debit' => $amount, 'credit' => 0],
                ['account_id' => $revenueAccountId, 'debit' => 0,       'credit' => $amount],
            ]
        );

        return $this->postEntry($entry);
    }

    /** DR Cash/Bank  CR AR */
    public function recordPayment(
        int $companyId, int $cashAccountId, int $arAccountId,
        float $amount, int $referenceId, string $date = null,
    ): JournalEntry {
        $entry = $this->createEntry(
            ['company_id' => $companyId, 'description' => "Payment for Invoice #{$referenceId}", 'entry_date' => $date ?? today()->toDateString(), 'reference_type' => 'invoice', 'reference_id' => $referenceId],
            [
                ['account_id' => $cashAccountId, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $arAccountId,   'debit' => 0,       'credit' => $amount],
            ]
        );

        return $this->postEntry($entry);
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    /** @throws InvalidJournalEntryException */
    private function validateLines(int $companyId, array $lines): void
    {
        if (count($lines) < 2) {
            throw InvalidJournalEntryException::tooFewLines(count($lines));
        }

        $totalDebit  = '0';
        $totalCredit = '0';

        foreach ($lines as $index => $line) {
            $debit  = (string) ($line['debit']  ?? 0);
            $credit = (string) ($line['credit'] ?? 0);

            $hasDebit  = bccomp($debit,  '0', 2) > 0;
            $hasCredit = bccomp($credit, '0', 2) > 0;

            if ($hasDebit === $hasCredit) {
                throw InvalidJournalEntryException::invalidLine($index);
            }

            $totalDebit  = bcadd($totalDebit,  $debit,  2);
            $totalCredit = bcadd($totalCredit, $credit, 2);
        }

        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            throw InvalidJournalEntryException::unbalanced($totalDebit, $totalCredit);
        }

        $this->assertAccountsBelongToCompany($companyId, array_column($lines, 'account_id'));
    }

    /** @throws InvalidJournalEntryException */
    private function assertAccountsBelongToCompany(int $companyId, array $accountIds): void
    {
        $validIds = Account::where('tenant_id', $companyId)
            ->whereIn('id', $accountIds)
            ->pluck('id')
            ->all();

        foreach ($accountIds as $id) {
            if (! in_array($id, $validIds, true)) {
                throw InvalidJournalEntryException::foreignAccount($id, $companyId);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Entry number generation
    // -------------------------------------------------------------------------

    private function generateEntryNumber(int $companyId): string
    {
        $year = now()->year;

        $count = JournalEntry::where('tenant_id', $companyId)
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->count();

        return sprintf('JE-%d-%05d', $year, $count + 1);
    }
}

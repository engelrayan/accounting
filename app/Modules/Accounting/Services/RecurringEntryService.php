<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\RecurringJournalEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecurringEntryService
{
    public function __construct(
        private readonly JournalEntryService $journalEntryService,
    ) {}

    // -------------------------------------------------------------------------
    // Create

    public function create(int $companyId, array $data, int $userId): RecurringJournalEntry
    {
        $lines = $this->parseLines($data['lines'] ?? []);

        if (empty($lines)) {
            throw new \DomainException('يجب إضافة سطر واحد على الأقل في القيد.');
        }

        $totalDebit  = collect($lines)->where('type', 'debit')->sum('amount');
        $totalCredit = collect($lines)->where('type', 'credit')->sum('amount');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \DomainException("القيد غير متوازن: مجموع المدين ({$totalDebit}) ≠ مجموع الدائن ({$totalCredit}).");
        }

        return RecurringJournalEntry::create([
            'company_id'    => $companyId,
            'description'   => $data['description'],
            'frequency'     => $data['frequency'],
            'start_date'    => $data['start_date'],
            'next_run_date' => $data['start_date'],
            'end_date'      => $data['end_date'] ?? null,
            'is_active'     => true,
            'lines'         => $lines,
            'created_by'    => $userId,
        ]);
    }

    // -------------------------------------------------------------------------
    // Toggle active

    public function toggle(RecurringJournalEntry $entry): RecurringJournalEntry
    {
        $entry->update(['is_active' => !$entry->is_active]);
        return $entry;
    }

    // -------------------------------------------------------------------------
    // Generate due entries

    /**
     * Generate journal entries for all due recurring templates.
     * Returns the count of entries generated.
     */
    public function generateDue(int $companyId): int
    {
        $due = RecurringJournalEntry::forCompany($companyId)
            ->due()
            ->get();

        $count = 0;

        foreach ($due as $template) {
            // Skip if past end date
            if ($template->end_date && now()->gt($template->end_date)) {
                $template->update(['is_active' => false]);
                continue;
            }

            DB::transaction(function () use ($template, &$count) {
                $lines = collect($template->lines)->map(fn ($l) => [
                    'account_id'  => $l['account_id'],
                    'debit'       => $l['type'] === 'debit'  ? (float) $l['amount'] : 0,
                    'credit'      => $l['type'] === 'credit' ? (float) $l['amount'] : 0,
                    'description' => $l['description'] ?? $template->description,
                ])->toArray();

                $this->journalEntryService->createEntry([
                    'company_id'     => $template->company_id,
                    'description'    => $template->description . ' (تلقائي)',
                    'entry_date'     => $template->next_run_date->toDateString(),
                    'reference_type' => 'recurring_journal_entry',
                    'reference_id'   => $template->id,
                ], $lines);

                $nextRun = $template->computeNextRunDate($template->next_run_date);

                $template->update([
                    'last_run_date' => $template->next_run_date,
                    'next_run_date' => $nextRun,
                ]);

                $count++;
            });
        }

        return $count;
    }

    // -------------------------------------------------------------------------
    // Helpers

    private function parseLines(array $rawLines): array
    {
        $lines = [];

        foreach ($rawLines as $line) {
            $amount = (float) ($line['amount'] ?? 0);
            if ($amount <= 0 || empty($line['account_id'])) continue;

            $lines[] = [
                'account_id'  => (int) $line['account_id'],
                'type'        => in_array($line['type'] ?? '', ['debit', 'credit']) ? $line['type'] : 'debit',
                'amount'      => $amount,
                'description' => $line['description'] ?? null,
            ];
        }

        return $lines;
    }
}

<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Partner;
use App\Modules\Accounting\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(
        private readonly JournalEntryService $journalService
    ) {}

    /**
     * Create a business-friendly transaction and its underlying journal entry.
     *
     * Accounting mapping (DR = to_account, CR = from_account):
     *   expense          → DR expense_account   / CR cash_account
     *   income           → DR cash_account       / CR revenue_account
     *   transfer         → DR to_account         / CR from_account
     *   capital_addition → DR cash_account       / CR partner capital account
     *   withdrawal       → DR partner drawing    / CR cash_account
     *
     * @throws \App\Modules\Accounting\Exceptions\InvalidJournalEntryException
     * @throws \DomainException
     */
    public function create(array $data, int $companyId): Transaction
    {
        [$fromAccountId, $toAccountId] = $this->resolveAccounts($data, $companyId);

        $amount = (float) $data['amount'];
        $date   = $data['transaction_date'];
        $type   = $data['type'];

        $prefix = match ($type) {
            'expense'          => 'مصروف',
            'income'           => 'إيراد',
            'transfer'         => 'تحويل',
            'capital_addition' => 'إضافة رأس مال',
            'withdrawal'       => 'سحب',
        };

        $jeDescription = $data['description']
            ? "{$prefix}: {$data['description']}"
            : $prefix;

        return DB::transaction(function () use ($data, $companyId, $amount, $date, $jeDescription, $type, $fromAccountId, $toAccountId) {
            $entry = $this->journalService->createEntry(
                [
                    'company_id'     => $companyId,
                    'description'    => $jeDescription,
                    'entry_date'     => $date,
                    'reference_type' => 'transaction',
                ],
                [
                    ['account_id' => $toAccountId,   'debit' => $amount, 'credit' => 0],
                    ['account_id' => $fromAccountId, 'debit' => 0,       'credit' => $amount],
                ]
            );

            $this->journalService->postEntry($entry);

            $transaction = Transaction::create([
                'company_id'       => $companyId,
                'journal_entry_id' => $entry->id,
                'type'             => $type,
                'from_account_id'  => $fromAccountId,
                'to_account_id'    => $toAccountId,
                'amount'           => $amount,
                'description'      => $data['description'] ?? null,
                'transaction_date' => $date,
            ]);

            $entry->update(['reference_id' => $transaction->id]);

            return $transaction;
        });
    }

    // -------------------------------------------------------------------------

    /**
     * Map type-specific form fields to [from_account_id, to_account_id].
     *   from_account_id = CR side (where money comes from)
     *   to_account_id   = DR side (where money goes)
     */
    private function resolveAccounts(array $data, int $companyId): array
    {
        return match ($data['type']) {
            'expense' => [
                (int) $data['cash_account_id'],     // CR cash
                (int) $data['expense_account_id'],  // DR expense
            ],
            'income' => [
                (int) $data['revenue_account_id'],  // CR revenue
                (int) $data['cash_account_id'],     // DR cash
            ],
            'transfer' => [
                (int) $data['from_account_id'],     // CR source
                (int) $data['to_account_id'],       // DR destination
            ],
            'capital_addition' => [
                $this->partnerAccount($data['partner_id'], $companyId, 'capital'), // CR capital
                (int) $data['cash_account_id'],                                    // DR cash
            ],
            'withdrawal' => [
                (int) $data['cash_account_id'],                                    // CR cash
                $this->partnerAccount($data['partner_id'], $companyId, 'drawing'), // DR drawing
            ],
            default => throw new \DomainException("نوع المعاملة غير معروف: {$data['type']}"),
        };
    }

    private function partnerAccount(int $partnerId, int $companyId, string $side): int
    {
        $partner = Partner::where('id', $partnerId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        return $side === 'capital'
            ? $partner->capital_account_id
            : $partner->drawing_account_id;
    }
}

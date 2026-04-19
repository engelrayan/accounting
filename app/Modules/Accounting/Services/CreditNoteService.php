<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\CreditNote;
use App\Modules\Accounting\Models\Invoice;
use Illuminate\Support\Facades\DB;

class CreditNoteService
{
    public function __construct(
        private readonly JournalEntryService $journalEntryService,
    ) {}

    // -------------------------------------------------------------------------
    // Credit note number
    // -------------------------------------------------------------------------

    /**
     * Next sequential credit note number.
     * Format: CN-{year}-{zero-padded-4-digit-seq}
     */
    public function nextNumber(int $companyId): string
    {
        $year   = now()->year;
        $prefix = "CN-{$year}-";

        $last = CreditNote::where('company_id', $companyId)
            ->where('credit_note_number', 'like', "{$prefix}%")
            ->orderByDesc('credit_note_number')
            ->value('credit_note_number');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    /**
     * Issue a credit note against an invoice.
     *
     * $data keys:
     *   amount      — base amount to credit (before tax)
     *   reason?     — free-text reason
     *   issue_date  — date string YYYY-MM-DD
     *
     * GL posted:
     *   DR إيرادات المبيعات (4100)  — amount (base)
     *   DR ضريبة مخرجات     (2130)  — tax_amount  (if > 0)
     *   CR ذمم مدينة        (1130)  — total
     *
     * @throws \DomainException
     */
    public function create(Invoice $invoice, array $data): CreditNote
    {
        $amount = round((float) $data['amount'], 2);

        if ($amount <= 0) {
            throw new \DomainException('يجب أن يكون مبلغ الإشعار أكبر من صفر.');
        }

        // Tax is proportional to the invoice's own tax_rate
        $taxRate   = (float) $invoice->tax_rate;
        $taxAmount = round($amount * $taxRate / 100, 2);
        $total     = round($amount + $taxAmount, 2);

        // Cannot exceed what's still owed on the invoice
        $maxCredit = (float) $invoice->remaining_amount;
        if ($total > $maxCredit + 0.001) {
            throw new \DomainException(
                'إجمالي الإشعار (' . number_format($total, 2) . ') ' .
                'أكبر من المتبقي على الفاتورة (' . number_format($maxCredit, 2) . ').'
            );
        }

        return DB::transaction(function () use ($invoice, $data, $amount, $taxAmount, $total) {

            $creditNote = CreditNote::create([
                'company_id'         => $invoice->company_id,
                'customer_id'        => $invoice->customer_id,
                'invoice_id'         => $invoice->id,
                'credit_note_number' => $this->nextNumber($invoice->company_id),
                'reason'             => $data['reason'] ?? null,
                'amount'             => $amount,
                'tax_amount'         => $taxAmount,
                'total'              => $total,
                'status'             => 'issued',
                'issue_date'         => $data['issue_date'],
            ]);

            // ── Update invoice: recompute credited_amount from all credit notes
            $invoice->credited_amount = CreditNote::where('invoice_id', $invoice->id)
                ->where('status', 'issued')
                ->sum('total');
            $invoice->save(); // booted() syncs remaining_amount + status

            // ── Post GL entry ─────────────────────────────────────────────────
            $this->postGlEntry($creditNote, $invoice);

            return $creditNote;
        });
    }

    // -------------------------------------------------------------------------
    // GL posting
    // -------------------------------------------------------------------------

    /**
     * DR إيرادات المبيعات (4100)  — base amount
     * DR ضريبة مخرجات     (2130)  — tax_amount (skipped if zero)
     * CR ذمم مدينة        (1130)  — total
     */
    private function postGlEntry(CreditNote $creditNote, Invoice $invoice): void
    {
        $companyId = $creditNote->company_id;

        $arAccount      = $this->resolveAccount($companyId, '1130', 'asset',     ['ذمم مدينة', 'عملاء']);
        $revenueAccount = $this->resolveOrCreateAccount($companyId, '4100', 'revenue', 'إيرادات المبيعات', 'credit', '4000');

        $customerName = $invoice->customer?->name ?? '—';
        $description  = "إشعار دائن [{$creditNote->credit_note_number}] — الفاتورة [{$invoice->invoice_number}] — {$customerName}";

        $lines = [
            ['account_id' => $revenueAccount->id, 'debit' => (float) $creditNote->amount, 'credit' => 0],
        ];

        if ((float) $creditNote->tax_amount > 0) {
            $vatAccount = $this->resolveOrCreateAccount($companyId, '2130', 'liability', 'ضريبة القيمة المضافة المخرجات', 'credit', '2100');
            $lines[]    = ['account_id' => $vatAccount->id, 'debit' => (float) $creditNote->tax_amount, 'credit' => 0];
        }

        $lines[] = ['account_id' => $arAccount->id, 'debit' => 0, 'credit' => (float) $creditNote->total];

        $entry = $this->journalEntryService->createEntry(
            [
                'company_id'     => $companyId,
                'description'    => $description,
                'entry_date'     => $creditNote->issue_date->toDateString(),
                'reference_type' => 'credit_note',
                'reference_id'   => $creditNote->id,
            ],
            $lines
        );

        $this->journalEntryService->postEntry($entry);
    }

    // -------------------------------------------------------------------------
    // Account resolution helpers (same pattern as PurchaseInvoiceService)
    // -------------------------------------------------------------------------

    private function resolveAccount(int $companyId, string $code, string $type, array $nameFragments): Account
    {
        $account = Account::where('tenant_id', $companyId)
            ->where('type', $type)
            ->where(function ($q) use ($code, $nameFragments) {
                $q->where('code', $code);
                foreach ($nameFragments as $fragment) {
                    $q->orWhere('name', 'like', "%{$fragment}%");
                }
            })
            ->orderByRaw('CASE WHEN code = ? THEN 0 ELSE 1 END', [$code])
            ->first();

        if (! $account) {
            throw new \RuntimeException(
                "الحساب (كود: {$code}) غير موجود في دليل الحسابات. يرجى إنشاؤه أولاً."
            );
        }

        return $account;
    }

    private function resolveOrCreateAccount(
        int    $companyId,
        string $code,
        string $type,
        string $name,
        string $normalBalance,
        string $parentCode,
    ): Account {
        $existing = Account::where('tenant_id', $companyId)->where('code', $code)->first();
        if ($existing) return $existing;

        $parent = Account::where('tenant_id', $companyId)->where('code', $parentCode)->first();

        return Account::create([
            'tenant_id'      => $companyId,
            'parent_id'      => $parent?->id,
            'code'           => $code,
            'name'           => $name,
            'type'           => $type,
            'normal_balance' => $normalBalance,
            'is_system'      => false,
            'is_active'      => true,
        ]);
    }
}

<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\Invoice;
use App\Modules\Accounting\Models\InvoiceItem;
use App\Modules\Accounting\Models\Payment;
use App\Modules\Accounting\Models\Product;
use App\Modules\Accounting\Services\CompanySettingsService;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private readonly CompanySettingsService $settingsService,
        private readonly JournalEntryService    $journalEntryService,
        private readonly InventoryService       $inventoryService,
    ) {}

    // -------------------------------------------------------------------------
    // Invoice number
    // -------------------------------------------------------------------------

    /**
     * Next sequential invoice number for this company.
     * Format: INV-{year}-{zero-padded-4-digit-seq}
     */
    public function nextNumber(int $companyId): string
    {
        $year   = now()->year;
        $prefix = "INV-{$year}-";

        $last = Invoice::where('company_id', $companyId)
            ->where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    /**
     * Create an invoice with its line items inside a transaction.
     *
     * GL posted automatically:
     *   DR ذمم مدينة (1130)          — total (subtotal + tax)
     *   CR إيرادات المبيعات (4100)   — subtotal
     *   CR ضريبة مخرجات (2130)       — tax_amount (if > 0)
     *
     * $data keys:
     *   customer_id, issue_date, due_date?, payment_method?, notes?
     *   tax_rate? (overrides company default when explicitly provided)
     *   items: [ [description, quantity, unit_price], … ]
     */
    public function create(array $data, int $companyId): Invoice
    {
        return DB::transaction(function () use ($data, $companyId) {

            // ── Subtotal from line items ──────────────────────────────────────
            $subtotal = collect($data['items'])
                ->sum(fn ($i) => round((float) $i['quantity'] * (float) $i['unit_price'], 2));

            $discountAmount = round((float) ($data['discount_amount'] ?? 0), 2);
            $discountAmount = max(0, min($discountAmount, $subtotal));
            $netSubtotal = round($subtotal - $discountAmount, 2);

            // ── Tax ──────────────────────────────────────────────────────────
            $settings = $this->settingsService->forCompany($companyId);

            if (array_key_exists('tax_rate', $data)) {
                $taxRate = (float) $data['tax_rate'];
            } elseif ($settings->taxEnabled()) {
                $taxRate = $settings->taxRate();
            } else {
                $taxRate = 0.0;
            }

            $taxAmount = round($netSubtotal * $taxRate / 100, 2);
            $amount    = round($netSubtotal + $taxAmount, 2);

            $invoice = Invoice::create([
                'company_id'     => $companyId,
                'customer_id'    => $data['customer_id'],
                'created_by'     => auth()->id(),
                'invoice_number' => $this->nextNumber($companyId),
                'notes'          => $data['notes']          ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'source'         => $data['source']         ?? 'invoice',
                'subtotal'       => $subtotal,
                'discount_amount'=> $discountAmount,
                'tax_rate'       => $taxRate,
                'tax_amount'     => $taxAmount,
                'amount'         => $amount,
                'paid_amount'    => 0,
                'issue_date'     => $data['issue_date'],
                'due_date'       => $data['due_date'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'product_id'  => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total'       => 0,
                ]);
            }

            // ── GL: DR ذمم مدينة  CR إيرادات (+ CR ضريبة) ────────────────
            $this->postInvoiceJournalEntry($invoice);

            // ── Inventory: تسجيل حركة البيع لكل منتج مادي ────────────────
            $warehouse = $this->inventoryService->getDefaultWarehouse($companyId);
            $userId    = auth()->id() ?? 1;

            foreach ($data['items'] as $item) {
                if (empty($item['product_id'])) continue;

                $product = Product::find($item['product_id']);
                if (! $product || ! $product->isProduct()) continue;

                $this->inventoryService->recordSale(
                    $product,
                    $warehouse,
                    (float) $item['quantity'],
                    'invoice',
                    $invoice->id,
                    $userId,
                    $companyId,
                );
            }

            return $invoice;
        });
    }

    // -------------------------------------------------------------------------
    // Record payment
    // -------------------------------------------------------------------------

    /**
     * Create a Payment record, sync invoice totals, and post the GL entry.
     *
     * GL posted automatically:
     *   DR نقد/بنك (1110/1120)   — payment amount
     *   CR ذمم مدينة (1130)      — payment amount
     *
     * @throws \DomainException
     */
    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        if ($invoice->status === 'cancelled') {
            throw new \DomainException('لا يمكن تسجيل دفعة على فاتورة ملغاة.');
        }

        $amount  = (float) $data['amount'];
        $current = (float) $invoice->remaining_amount;

        if ($amount > $current + 0.001) {
            throw new \DomainException(
                'المبلغ أكبر من المتبقي (' . number_format($current, 2) . ').'
            );
        }

        return DB::transaction(function () use ($invoice, $data, $amount) {

            $payment = Payment::create([
                'company_id'     => $invoice->company_id,
                'customer_id'    => $invoice->customer_id,
                'invoice_id'     => $invoice->id,
                'amount'         => $amount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_date'   => $data['payment_date']   ?? now()->toDateString(),
                'notes'          => $data['notes']          ?? null,
            ]);

            // Recompute paid_amount from SUM — single source of truth
            $invoice->paid_amount = Payment::where('invoice_id', $invoice->id)->sum('amount');
            $invoice->save();

            // ── GL: DR نقد/بنك  CR ذمم مدينة ─────────────────────────────
            $this->postPaymentJournalEntry($payment, $invoice);

            return $payment;
        });
    }

    // -------------------------------------------------------------------------
    // GL helpers
    // -------------------------------------------------------------------------

    /**
     * DR ذمم مدينة (1130)          — total (subtotal + tax)
     * CR إيرادات المبيعات (4100)   — subtotal
     * CR ضريبة مخرجات (2130)       — tax_amount (if > 0)
     */
    private function postInvoiceJournalEntry(Invoice $invoice): void
    {
        $companyId = $invoice->company_id;

        $arAccount      = $this->resolveOrCreateAccount($companyId, '1130', 'asset', 'ذمم مدينة (عملاء)', 'debit', '1100');
        $revenueAccount = $this->resolveOrCreateAccount($companyId, '4100', 'revenue', 'إيرادات المبيعات', 'credit', '4000');

        $customerName = $invoice->customer?->name ?? '—';
        $description  = "فاتورة مبيعات [{$invoice->invoice_number}] — العميل: {$customerName}";

        $netRevenue = max(0, (float) $invoice->subtotal - (float) $invoice->discount_amount);

        $lines = [
            ['account_id' => $arAccount->id,      'debit' => (float) $invoice->amount, 'credit' => 0],
            ['account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => $netRevenue],
        ];

        if ((float) $invoice->tax_amount > 0) {
            $vatAccount = $this->resolveOrCreateAccount($companyId, '2130', 'liability', 'ضريبة القيمة المضافة المخرجات', 'credit', '2100');
            $lines[]    = ['account_id' => $vatAccount->id, 'debit' => 0, 'credit' => (float) $invoice->tax_amount];
        }

        $entry = $this->journalEntryService->createEntry(
            [
                'company_id'     => $companyId,
                'description'    => $description,
                'entry_date'     => $invoice->issue_date->toDateString(),
                'reference_type' => 'invoice',
                'reference_id'   => $invoice->id,
            ],
            $lines
        );

        $this->journalEntryService->postEntry($entry);
    }

    /**
     * DR نقد/بنك (1110/1120)   — payment amount
     * CR ذمم مدينة (1130)      — payment amount
     */
    private function postPaymentJournalEntry(Payment $payment, Invoice $invoice): void
    {
        $companyId = $payment->company_id;

        $arAccount = $this->resolveOrCreateAccount($companyId, '1130', 'asset', 'ذمم مدينة (عملاء)', 'debit', '1100');

        $cashAccount = in_array($payment->payment_method, ['bank', 'bank_transfer', 'instapay', 'cheque'])
            ? $this->resolveOrCreateAccount($companyId, '1120', 'asset', 'البنك', 'debit', '1100')
            : $this->resolveOrCreateAccount($companyId, '1110', 'asset', 'الصندوق', 'debit', '1100');

        $methodLabels = [
            'cash'          => 'نقداً',
            'bank'          => 'بنكي',
            'bank_transfer' => 'تحويل بنكي',
            'wallet'        => 'محفظة',
            'instapay'      => 'إنستاباي',
            'cheque'        => 'شيك',
            'card'          => 'بطاقة',
            'other'         => 'أخرى',
        ];
        $methodLabel = $methodLabels[$payment->payment_method] ?? $payment->payment_method;
        $description = "دفعة عميل — الفاتورة [{$invoice->invoice_number}] — {$methodLabel}";

        $entry = $this->journalEntryService->createEntry(
            [
                'company_id'     => $companyId,
                'description'    => $description,
                'entry_date'     => $payment->payment_date->toDateString(),
                'reference_type' => 'payment',
                'reference_id'   => $payment->id,
            ],
            [
                ['account_id' => $cashAccount->id, 'debit' => (float) $payment->amount, 'credit' => 0],
                ['account_id' => $arAccount->id,   'debit' => 0, 'credit' => (float) $payment->amount],
            ]
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

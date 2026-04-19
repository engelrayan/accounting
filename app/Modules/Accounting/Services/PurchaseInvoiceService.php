<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\Product;
use App\Modules\Accounting\Models\PurchaseInvoice;
use App\Modules\Accounting\Models\PurchaseInvoiceItem;
use App\Modules\Accounting\Models\PurchasePayment;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceService
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
     * Next sequential bill number for this company.
     * Format: BILL-{year}-{zero-padded-4-digit-seq}
     */
    public function nextNumber(int $companyId): string
    {
        $year   = now()->year;
        $prefix = "BILL-{$year}-";

        $last = PurchaseInvoice::where('company_id', $companyId)
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
     * Create a purchase invoice with line items and post the GL entry.
     *
     * GL: DR Purchases (5100)  CR Accounts Payable (2110)
     *
     * $data keys:
     *   vendor_id, issue_date, due_date?, payment_method?, notes?,
     *   vendor_invoice_number?
     *   tax_rate? (overrides company default)
     *   items: [ [description, quantity, unit_price], … ]
     */
    public function create(array $data, int $companyId): PurchaseInvoice
    {
        return DB::transaction(function () use ($data, $companyId) {

            // ── Subtotal ─────────────────────────────────────────────────────
            $subtotal = collect($data['items'])
                ->sum(fn ($i) => round((float) $i['quantity'] * (float) $i['unit_price'], 2));

            // ── Tax ──────────────────────────────────────────────────────────
            $settings = $this->settingsService->forCompany($companyId);

            if (array_key_exists('tax_rate', $data)) {
                $taxRate = (float) $data['tax_rate'];
            } elseif ($settings->taxEnabled()) {
                $taxRate = $settings->taxRate();
            } else {
                $taxRate = 0.0;
            }

            $taxAmount = round($subtotal * $taxRate / 100, 2);
            $amount    = round($subtotal + $taxAmount, 2);

            $invoice = PurchaseInvoice::create([
                'company_id'            => $companyId,
                'vendor_id'             => $data['vendor_id'],
                'invoice_number'        => $this->nextNumber($companyId),
                'vendor_invoice_number' => $data['vendor_invoice_number'] ?? null,
                'notes'                 => $data['notes']          ?? null,
                'payment_method'        => $data['payment_method'] ?? null,
                'subtotal'              => $subtotal,
                'tax_rate'              => $taxRate,
                'tax_amount'            => $taxAmount,
                'amount'                => $amount,
                'paid_amount'           => 0,
                'issue_date'            => $data['issue_date'],
                'due_date'              => $data['due_date'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id'          => $item['product_id'] ?? null,
                    'description'         => $item['description'],
                    'quantity'            => $item['quantity'],
                    'unit_price'          => $item['unit_price'],
                    'total'               => 0, // booted() computes it
                ]);
            }

            // ── GL: DR Inventory/Purchases  CR Accounts Payable ───────────
            $this->postPurchaseJournalEntry($invoice, $data['items']);

            // ── Inventory: تسجيل حركة الشراء لكل منتج مادي ───────────────
            $warehouse = $this->inventoryService->getDefaultWarehouse($companyId);
            $userId    = auth()->id() ?? 1;

            foreach ($data['items'] as $item) {
                if (empty($item['product_id'])) continue;

                $product = Product::find($item['product_id']);
                if (! $product || ! $product->isProduct()) continue;

                $this->inventoryService->recordPurchase(
                    $product,
                    $warehouse,
                    (float) $item['quantity'],
                    (float) $item['unit_price'],
                    'purchase_invoice',
                    $invoice->id,
                    $userId,
                );
            }

            return $invoice;
        });
    }

    // -------------------------------------------------------------------------
    // Record payment
    // -------------------------------------------------------------------------

    /**
     * Record a payment to a vendor and post the GL entry.
     *
     * GL: DR Accounts Payable (2110)  CR Cash/Bank (1110/1120)
     *
     * @throws \DomainException
     */
    public function recordPayment(PurchaseInvoice $invoice, array $data): PurchasePayment
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

            $payment = PurchasePayment::create([
                'company_id'          => $invoice->company_id,
                'vendor_id'           => $invoice->vendor_id,
                'purchase_invoice_id' => $invoice->id,
                'amount'              => $amount,
                'payment_method'      => $data['payment_method'] ?? 'cash',
                'payment_date'        => $data['payment_date']   ?? now()->toDateString(),
                'notes'               => $data['notes']          ?? null,
            ]);

            // Recompute paid_amount from SUM — single source of truth
            $invoice->paid_amount = PurchasePayment::where('purchase_invoice_id', $invoice->id)->sum('amount');
            $invoice->save(); // booted() syncs remaining_amount + status

            // ── GL: DR Accounts Payable  CR Cash/Bank ─────────────────────
            $this->postPaymentJournalEntry($payment, $invoice);

            return $payment;
        });
    }

    // -------------------------------------------------------------------------
    // GL helpers
    // -------------------------------------------------------------------------

    /**
     * DR المخزون (1300) — product items
     * DR المشتريات (5100) — service items + tax
     * CR ذمم دائنة (2110) — total
     */
    private function postPurchaseJournalEntry(PurchaseInvoice $invoice, array $items): void
    {
        $companyId        = $invoice->company_id;
        $apAccount        = $this->resolveAccount($companyId, '2110', 'liability', ['ذمم دائنة', 'موردون', 'موردين']);
        $purchaseAccount  = $this->resolveOrCreateAccount($companyId, '5100', 'expense', 'المشتريات',  'debit', '5000');
        $inventoryAccount = $this->resolveOrCreateAccount($companyId, '1300', 'asset',   'المخزون',    'debit', '1000');

        $inventoryTotal = 0.0;
        $purchasesTotal = 0.0;

        foreach ($items as $item) {
            $lineTotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
            $isInventory = false;

            if (! empty($item['product_id'])) {
                $product = Product::find($item['product_id']);
                if ($product && $product->isProduct()) {
                    $isInventory = true;
                }
            }

            if ($isInventory) {
                $inventoryTotal += $lineTotal;
            } else {
                $purchasesTotal += $lineTotal;
            }
        }

        // Tax always goes to purchases/expenses
        $purchasesTotal += (float) $invoice->tax_amount;

        $vendorName  = $invoice->vendor?->name ?? '—';
        $description = "فاتورة مشتريات [{$invoice->invoice_number}] — المورد: {$vendorName}";

        $lines = [
            ['account_id' => $apAccount->id, 'debit' => 0, 'credit' => (float) $invoice->amount],
        ];

        if ($inventoryTotal > 0) {
            $lines[] = ['account_id' => $inventoryAccount->id, 'debit' => round($inventoryTotal, 2), 'credit' => 0];
        }
        if ($purchasesTotal > 0) {
            $lines[] = ['account_id' => $purchaseAccount->id, 'debit' => round($purchasesTotal, 2), 'credit' => 0];
        }

        $entry = $this->journalEntryService->createEntry(
            [
                'company_id'     => $companyId,
                'description'    => $description,
                'entry_date'     => $invoice->issue_date->toDateString(),
                'reference_type' => 'purchase_invoice',
                'reference_id'   => $invoice->id,
            ],
            $lines
        );

        $this->journalEntryService->postEntry($entry);
    }

    /**
     * DR Accounts Payable (2110)  CR Cash/Bank (1110/1120)
     */
    private function postPaymentJournalEntry(PurchasePayment $payment, PurchaseInvoice $invoice): void
    {
        $companyId = $payment->company_id;
        $apAccount = $this->resolveAccount($companyId, '2110', 'liability', ['ذمم دائنة', 'موردون', 'موردين']);

        $cashAccount = in_array($payment->payment_method, ['bank', 'instapay', 'cheque'])
            ? $this->resolveOrCreateAccount($companyId, '1120', 'asset', 'البنك', 'debit', '1100')
            : $this->resolveOrCreateAccount($companyId, '1110', 'asset', 'الصندوق', 'debit', '1100');

        $methodLabel = PurchasePayment::methodLabel($payment->payment_method);
        $description = "دفعة للمورد — الفاتورة [{$invoice->invoice_number}] — {$methodLabel}";

        $entry = $this->journalEntryService->createEntry(
            [
                'company_id'     => $companyId,
                'description'    => $description,
                'entry_date'     => $payment->payment_date->toDateString(),
                'reference_type' => 'purchase_payment',
                'reference_id'   => $payment->id,
            ],
            [
                ['account_id' => $apAccount->id,   'debit' => (float) $payment->amount, 'credit' => 0],
                ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => (float) $payment->amount],
            ]
        );

        $this->journalEntryService->postEntry($entry);
    }

    // -------------------------------------------------------------------------
    // Account resolution helpers
    // -------------------------------------------------------------------------

    private function resolveAccount(int $companyId, string $code, string $type, array $nameFragments): Account
    {
        $query = Account::where('tenant_id', $companyId)->where('type', $type);

        $account = $query->where(function ($q) use ($code, $nameFragments) {
            $q->where('code', $code);
            foreach ($nameFragments as $fragment) {
                $q->orWhere('name', 'like', "%{$fragment}%");
            }
        })
        ->orderByRaw("CASE WHEN code = ? THEN 0 ELSE 1 END", [$code])
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

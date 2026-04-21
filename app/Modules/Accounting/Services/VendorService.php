<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\PurchaseInvoice;
use App\Modules\Accounting\Models\PurchasePayment;
use App\Modules\Accounting\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VendorService
{
    public function __construct(
        private readonly JournalEntryService      $journalEntryService,
        private readonly PurchaseInvoiceService   $purchaseInvoiceService,
    ) {}

    // -------------------------------------------------------------------------
    // Balance
    // -------------------------------------------------------------------------

    /**
     * Vendor's outstanding balance (what we owe the vendor):
     *   opening_balance + total_purchases − total_paid
     *
     * Positive  → we owe the vendor (payable)
     * Zero      → fully settled
     * Negative  → vendor has credit with us
     */
    public function getBalance(Vendor $vendor): float
    {
        $row = DB::table('purchase_invoices')
            ->where('vendor_id', $vendor->id)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('SUM(amount) as invoiced, SUM(paid_amount) as paid')
            ->first();

        $totalPaid = PurchasePayment::where('vendor_id', $vendor->id)->sum('amount');

        return (float) $vendor->opening_balance + (float) ($row->invoiced ?? 0) - (float) $totalPaid;
    }

    public function getTotalInvoiced(Vendor $vendor): float
    {
        return (float) PurchaseInvoice::where('vendor_id', $vendor->id)
            ->where('status', '!=', 'cancelled')
            ->sum('amount');
    }

    public function getTotalPaid(Vendor $vendor): float
    {
        return (float) PurchasePayment::where('vendor_id', $vendor->id)->sum('amount');
    }

    /**
     * Return aggregates for ALL vendors of a company in one query.
     * Result is a Collection keyed by vendor_id, each item has:
     *   total_invoiced (float), total_paid (float)
     */
    public function bulkAggregates(int $companyId): Collection
    {
        $invoices = DB::table('purchase_invoices')
            ->where('company_id', $companyId)
            ->where('status', '!=', 'cancelled')
            ->groupBy('vendor_id')
            ->selectRaw('vendor_id, SUM(amount) as total_invoiced')
            ->get()
            ->keyBy('vendor_id');

        $payments = DB::table('purchase_payments')
            ->where('company_id', $companyId)
            ->groupBy('vendor_id')
            ->selectRaw('vendor_id, SUM(amount) as total_paid')
            ->get()
            ->keyBy('vendor_id');

        return $invoices->keys()
            ->merge($payments->keys())
            ->unique()
            ->mapWithKeys(fn ($vendorId) => [
                $vendorId => (object) [
                    'vendor_id' => $vendorId,
                    'total_invoiced' => (float) ($invoices->get($vendorId)->total_invoiced ?? 0),
                    'total_paid' => (float) ($payments->get($vendorId)->total_paid ?? 0),
                ],
            ]);
    }

    public function recordPayment(Vendor $vendor, array $data): array
    {
        $amount = round((float) $data['amount'], 2);
        $balance = round($this->getBalance($vendor), 2);

        if ($amount <= 0) {
            throw new \DomainException('المبلغ يجب أن يكون أكبر من صفر.');
        }

        if ($amount > $balance + 0.001) {
            throw new \DomainException(
                'المبلغ أكبر من المستحق (' . number_format($balance, 2) . ').'
            );
        }

        return DB::transaction(function () use ($vendor, $data, $amount) {
            $remaining = $amount;
            $invoiceAmount = 0.0;
            $openingAmount = 0.0;
            $payments = collect();

            foreach ($this->paymentAllocationInvoices($vendor, $data['purchase_invoice_id'] ?? null) as $invoice) {
                if ($remaining <= 0.001) {
                    break;
                }

                $invoiceRemaining = round((float) $invoice->remaining(), 2);
                if ($invoiceRemaining <= 0) {
                    continue;
                }

                $lineAmount = min($remaining, $invoiceRemaining);

                $payments->push($this->createPurchasePayment($vendor, $invoice, $lineAmount, $data));

                $invoice->paid_amount = PurchasePayment::where('purchase_invoice_id', $invoice->id)->sum('amount');
                $invoice->save();

                $invoiceAmount += $lineAmount;
                $remaining = round($remaining - $lineAmount, 2);
            }

            if ($remaining > 0.001) {
                $openingAmount = $remaining;
                $payments->push($this->createPurchasePayment($vendor, null, $openingAmount, $data, true));
            }

            $this->createVendorPaymentJournalEntry($vendor, $amount, $data);

            return [
                'payment' => $payments->first(),
                'payments' => $payments,
                'invoiceAmount' => round($invoiceAmount, 2),
                'openingAmount' => round($openingAmount, 2),
            ];
        });
    }

    private function paymentAllocationInvoices(Vendor $vendor, mixed $preferredInvoiceId): Collection
    {
        $query = PurchaseInvoice::where('vendor_id', $vendor->id)
            ->whereIn('status', ['pending', 'partial']);

        $invoices = $query
            ->orderByRaw('COALESCE(due_date, issue_date) asc')
            ->orderBy('id')
            ->get();

        if (! $preferredInvoiceId) {
            return $invoices;
        }

        $preferred = $invoices->firstWhere('id', (int) $preferredInvoiceId);
        if (! $preferred) {
            return $invoices;
        }

        return collect([$preferred])
            ->merge($invoices->reject(fn (PurchaseInvoice $invoice) => $invoice->id === $preferred->id))
            ->values();
    }

    private function createPurchasePayment(
        Vendor $vendor,
        ?PurchaseInvoice $invoice,
        float $amount,
        array $data,
        bool $againstOpeningBalance = false,
    ): PurchasePayment {
        $notes = $data['notes'] ?? null;

        if ($againstOpeningBalance) {
            $notes = trim(($notes ? "{$notes} - " : '') . 'سداد على حساب المورد / الرصيد الافتتاحي');
        }

        return PurchasePayment::create([
            'company_id' => $vendor->company_id,
            'vendor_id' => $vendor->id,
            'purchase_invoice_id' => $invoice?->id,
            'amount' => round($amount, 2),
            'payment_method' => $data['payment_method'] ?? 'cash',
            'payment_date' => $data['payment_date'] ?? now()->toDateString(),
            'notes' => $notes,
        ]);
    }

    private function createVendorPaymentJournalEntry(Vendor $vendor, float $amount, array $data): void
    {
        $companyId = $vendor->company_id;
        $method = $data['payment_method'] ?? 'cash';
        $paymentDate = $data['payment_date'] ?? now()->toDateString();

        $apAccount = $this->resolveOrCreateAccount(
            $companyId,
            '2110',
            'liability',
            'ذمم دائنة (موردون)',
            'credit',
            '2100',
        );

        $cashAccount = in_array($method, ['bank', 'instapay', 'cheque'], true)
            ? $this->resolveOrCreateAccount($companyId, '1120', 'asset', 'البنك', 'debit', '1100')
            : $this->resolveOrCreateAccount($companyId, '1110', 'asset', 'الصندوق', 'debit', '1100');

        $methodLabel = PurchasePayment::methodLabel($method);
        $description = "دفعة على حساب المورد [{$vendor->name}] — {$methodLabel}";

        $entry = $this->journalEntryService->createEntry(
            [
                'company_id' => $companyId,
                'description' => $description,
                'entry_date' => $paymentDate,
                'reference_type' => 'vendor_payment',
                'reference_id' => $vendor->id,
            ],
            [
                ['account_id' => $apAccount->id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => $amount],
            ],
        );

        $this->journalEntryService->postEntry($entry);
    }

    // -------------------------------------------------------------------------
    // Settlement
    // -------------------------------------------------------------------------

    /**
     * Settle all outstanding purchase invoices for a vendor atomically.
     *
     * GL entry:
     *   DR Accounts Payable (2110)  CR Purchase Discount Income (4500)
     *
     * Returns ['invoiceCount' => int, 'totalAmount' => float]
     */
    public function settleAccount(Vendor $vendor, string $settlementType = 'discount'): array
    {
        $today     = now()->toDateString();
        $companyId = $vendor->company_id;

        $invoices = PurchaseInvoice::where('vendor_id', $vendor->id)
            ->whereIn('status', ['pending', 'partial'])
            ->get();

        $settled     = 0;
        $totalAmount = 0.0;

        \Illuminate\Support\Facades\DB::transaction(function ()
            use ($vendor, $invoices, $today, $companyId, $settlementType, &$settled, &$totalAmount)
        {
            foreach ($invoices as $invoice) {
                $remaining = $invoice->remaining();
                if ($remaining <= 0) continue;

                $notes = ($settlementType === 'bad_debt')
                    ? 'إعدام دين - تسوية حساب مورد'
                    : 'خصم تجاري - تسوية حساب مورد';

                $this->purchaseInvoiceService->recordPayment($invoice, [
                    'amount'         => $remaining,
                    'payment_method' => 'settlement',
                    'payment_date'   => $today,
                    'notes'          => $notes,
                ]);

                $settled++;
                $totalAmount += $remaining;
            }

            if ($settled > 0 && $totalAmount > 0.0) {
                $this->createSettlementJournalEntry(
                    $companyId, $vendor->id, $totalAmount, $settlementType, $today
                );
            }
        });

        return [
            'invoiceCount' => $settled,
            'totalAmount'  => $totalAmount,
        ];
    }

    /**
     * Settlement GL entry:
     *   Discount : DR 2110 (AP)  CR 4500 (Purchase Discount Income)
     *   Bad Debt : DR 2110 (AP)  CR 4500 (Purchase Discount Income)  [same effect from AP side]
     */
    private function createSettlementJournalEntry(
        int    $companyId,
        int    $vendorId,
        float  $totalAmount,
        string $settlementType,
        string $date
    ): void {
        $apAccount     = $this->resolveApAccount($companyId);
        $incomeAccount = $this->resolveOrCreateIncomeAccount(
            $companyId, '4500', 'دخل خصومات الموردين'
        );

        $description = ($settlementType === 'bad_debt')
            ? "إعدام دين مورد - تسوية حساب مورد #{$vendorId}"
            : "خصم تجاري - تسوية حساب مورد #{$vendorId}";

        $entry = $this->journalEntryService->createEntry(
            [
                'company_id'     => $companyId,
                'description'    => $description,
                'entry_date'     => $date,
                'reference_type' => 'vendor',
                'reference_id'   => $vendorId,
            ],
            [
                ['account_id' => $apAccount->id,     'debit' => $totalAmount, 'credit' => 0],
                ['account_id' => $incomeAccount->id, 'debit' => 0,            'credit' => $totalAmount],
            ]
        );

        $this->journalEntryService->postEntry($entry);
    }

    /**
     * Resolve the Accounts Payable account (code 2110).
     * Falls back to any liability account whose name contains "ذمم دائنة" or "موردون".
     */
    private function resolveApAccount(int $companyId): Account
    {
        $account = Account::where('tenant_id', $companyId)
            ->where('type', 'liability')
            ->where(function ($q) {
                $q->where('code', '2110')
                  ->orWhere('name', 'like', '%ذمم دائنة%')
                  ->orWhere('name', 'like', '%موردون%')
                  ->orWhere('name', 'like', '%موردين%');
            })
            ->orderByRaw("CASE WHEN code = '2110' THEN 0 ELSE 1 END")
            ->first();

        if (! $account) {
            throw new \RuntimeException(
                'حساب الذمم الدائنة (2110) غير موجود في دليل الحسابات. ' .
                'يرجى إنشاؤه قبل إجراء التسوية.'
            );
        }

        return $account;
    }

    private function resolveOrCreateIncomeAccount(int $companyId, string $code, string $name): Account
    {
        $existing = Account::where('tenant_id', $companyId)->where('code', $code)->first();
        if ($existing) return $existing;

        $parent = Account::where('tenant_id', $companyId)->where('code', '4000')->first();

        return Account::create([
            'tenant_id'      => $companyId,
            'parent_id'      => $parent?->id,
            'code'           => $code,
            'name'           => $name,
            'type'           => 'revenue',
            'normal_balance' => 'credit',
            'is_system'      => false,
            'is_active'      => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // CRUD helpers
    // -------------------------------------------------------------------------

    public function createVendor(array $data, int $companyId): Vendor
    {
        return DB::transaction(function () use ($data, $companyId) {
            $vendor = Vendor::create([
                'company_id'      => $companyId,
                'name'            => $data['name'],
                'phone'           => $data['phone']           ?? null,
                'email'           => $data['email']           ?? null,
                'address'         => $data['address']         ?? null,
                'opening_balance' => $data['opening_balance'] ?? 0,
            ]);

            $this->postOpeningBalanceEntry($vendor);

            return $vendor;
        });
    }

    public function postOpeningBalanceEntry(Vendor $vendor): void
    {
        $amount = round((float) $vendor->opening_balance, 2);

        if ($amount <= 0) {
            return;
        }

        $alreadyPosted = JournalEntry::query()
            ->where('tenant_id', $vendor->company_id)
            ->where('reference_type', 'vendor_opening_balance')
            ->where('reference_id', $vendor->id)
            ->where('status', '!=', 'reversed')
            ->exists();

        if ($alreadyPosted) {
            return;
        }

        $apAccount = $this->resolveOrCreateAccount(
            $vendor->company_id,
            '2110',
            'liability',
            'ذمم دائنة (موردون)',
            'credit',
            '2100',
        );

        $openingEquityAccount = $this->resolveOrCreateAccount(
            $vendor->company_id,
            '3300',
            'equity',
            'الأرباح المحتجزة / أرصدة افتتاحية',
            'credit',
            '3000',
        );

        $entry = $this->journalEntryService->createEntry(
            [
                'company_id' => $vendor->company_id,
                'description' => "رصيد افتتاحي مستحق للمورد [{$vendor->name}]",
                'entry_date' => now()->toDateString(),
                'reference_type' => 'vendor_opening_balance',
                'reference_id' => $vendor->id,
            ],
            [
                [
                    'account_id' => $openingEquityAccount->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'تحميل الرصيد الافتتاحي على حقوق الملكية الافتتاحية',
                ],
                [
                    'account_id' => $apAccount->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => "التزام افتتاحي للمورد {$vendor->name}",
                ],
            ],
        );

        $this->journalEntryService->postEntry($entry);
    }

    private function resolveOrCreateAccount(
        int $companyId,
        string $code,
        string $type,
        string $name,
        string $normalBalance,
        string $parentCode,
    ): Account {
        $existing = Account::where('tenant_id', $companyId)->where('code', $code)->first();
        if ($existing) {
            return $existing;
        }

        $parent = Account::where('tenant_id', $companyId)->where('code', $parentCode)->first();

        return Account::create([
            'tenant_id' => $companyId,
            'parent_id' => $parent?->id,
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'normal_balance' => $normalBalance,
            'is_system' => true,
            'is_active' => true,
        ]);
    }
}

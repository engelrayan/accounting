<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\PurchaseInvoice;
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
            ->selectRaw('SUM(amount) as invoiced, SUM(paid_amount) as paid')
            ->first();

        return (float) $vendor->opening_balance + (float) ($row->invoiced ?? 0) - (float) ($row->paid ?? 0);
    }

    public function getTotalInvoiced(Vendor $vendor): float
    {
        return (float) PurchaseInvoice::where('vendor_id', $vendor->id)->sum('amount');
    }

    public function getTotalPaid(Vendor $vendor): float
    {
        return (float) PurchaseInvoice::where('vendor_id', $vendor->id)->sum('paid_amount');
    }

    /**
     * Return aggregates for ALL vendors of a company in one query.
     * Result is a Collection keyed by vendor_id, each item has:
     *   total_invoiced (float), total_paid (float)
     */
    public function bulkAggregates(int $companyId): Collection
    {
        return DB::table('purchase_invoices')
            ->where('company_id', $companyId)
            ->groupBy('vendor_id')
            ->selectRaw('vendor_id, SUM(amount) as total_invoiced, SUM(paid_amount) as total_paid')
            ->get()
            ->keyBy('vendor_id');
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
        return Vendor::create([
            'company_id'      => $companyId,
            'name'            => $data['name'],
            'phone'           => $data['phone']           ?? null,
            'email'           => $data['email']           ?? null,
            'address'         => $data['address']         ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
        ]);
    }
}

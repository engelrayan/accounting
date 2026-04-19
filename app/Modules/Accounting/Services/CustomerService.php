<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\Customer;
use App\Modules\Accounting\Models\CustomerInvoice;
use App\Modules\Accounting\Models\CustomerPayment;
use App\Modules\Accounting\Models\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Modules\Accounting\Services\InvoiceService;

class CustomerService
{
    public function __construct(
        private readonly JournalEntryService $journalEntryService,
        private readonly InvoiceService      $invoiceService,
    ) {}

    // -------------------------------------------------------------------------
    // Balance
    // -------------------------------------------------------------------------

    /**
     * Customer's outstanding balance:
     *   opening_balance + total_invoiced − total_paid
     *
     * Positive  → customer owes money (receivable)
     * Zero      → fully settled
     * Negative  → customer has credit
     */
    public function getBalance(Customer $customer): float
    {
        $row = DB::table('invoices')
            ->where('customer_id', $customer->id)
            ->selectRaw('SUM(amount) as invoiced, SUM(paid_amount) as paid')
            ->first();

        return (float) $customer->opening_balance + (float) ($row->invoiced ?? 0) - (float) ($row->paid ?? 0);
    }

    public function getTotalInvoiced(Customer $customer): float
    {
        return (float) Invoice::where('customer_id', $customer->id)->sum('amount');
    }

    public function getTotalPaid(Customer $customer): float
    {
        return (float) Invoice::where('customer_id', $customer->id)->sum('paid_amount');
    }

    /**
     * Return aggregates for ALL customers of a company in one query.
     * Result is a Collection keyed by customer_id, each item has:
     *   total_invoiced (float), total_paid (float)
     */
    public function bulkAggregates(int $companyId): Collection
    {
        return DB::table('invoices')
            ->where('company_id', $companyId)
            ->groupBy('customer_id')
            ->selectRaw('customer_id, SUM(amount) as total_invoiced, SUM(paid_amount) as total_paid')
            ->get()
            ->keyBy('customer_id');
    }

    // -------------------------------------------------------------------------
    // Settlement
    // -------------------------------------------------------------------------

    /**
     * Settle all outstanding invoices for a customer in one atomic transaction.
     *
     * For every unpaid invoice a CustomerPayment record is created with:
     *   - amount         = invoice.remaining()
     *   - payment_method = 'settlement'
     *
     * A balanced journal entry is also created and posted:
     *   - 'discount'  → DR Discount Expense (5700)  CR Accounts Receivable (1130)
     *   - 'bad_debt'  → DR Bad Debt Expense  (5800)  CR Accounts Receivable (1130)
     *
     * Returns an array with:
     *   - invoiceCount : number of invoices settled
     *   - totalAmount  : total amount settled
     */
    public function settleAccount(Customer $customer, string $settlementType = 'discount'): array
    {
        $today     = now()->toDateString();
        $companyId = $customer->company_id;

        // Load only unpaid/partial formal invoices
        $invoices = Invoice::where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'partial'])
            ->get();

        $settled     = 0;
        $totalAmount = 0.0;

        DB::transaction(function () use ($customer, $invoices, $today, $companyId, $settlementType, &$settled, &$totalAmount) {

            foreach ($invoices as $invoice) {
                $remaining = $invoice->remaining();

                if ($remaining <= 0) {
                    continue; // safety guard — skip fully paid
                }

                $notes = ($settlementType === 'bad_debt')
                    ? 'إعدام دين - تسوية حساب'
                    : 'خصم - تسوية حساب';

                $this->invoiceService->recordPayment($invoice, [
                    'amount'         => $remaining,
                    'payment_method' => 'settlement',
                    'payment_date'   => $today,
                    'notes'          => $notes,
                ]);

                $settled++;
                $totalAmount += $remaining;
            }

            // Create and post the accounting journal entry
            if ($settled > 0 && $totalAmount > 0.0) {
                $this->createSettlementJournalEntry(
                    $companyId, $customer->id, $totalAmount, $settlementType, $today
                );
            }
        });

        return [
            'invoiceCount' => $settled,
            'totalAmount'  => $totalAmount,
        ];
    }

    /**
     * Build and immediately post the settlement journal entry.
     *
     * Discount  : DR 5700 (Discount Expense)   CR 1130 (AR)
     * Bad Debt  : DR 5800 (Bad Debt Expense)   CR 1130 (AR)
     */
    private function createSettlementJournalEntry(
        int    $companyId,
        int    $customerId,
        float  $totalAmount,
        string $settlementType,
        string $date
    ): void {
        $arAccount = $this->resolveArAccount($companyId);

        if ($settlementType === 'bad_debt') {
            $expenseAccount = $this->resolveOrCreateExpenseAccount(
                $companyId, '5800', 'مصروف ديون معدومة'
            );
            $description = "إعدام ديون - تسوية حساب عميل #{$customerId}";
        } else {
            $expenseAccount = $this->resolveOrCreateExpenseAccount(
                $companyId, '5700', 'مصروف خصومات'
            );
            $description = "خصم - تسوية حساب عميل #{$customerId}";
        }

        $entry = $this->journalEntryService->createEntry(
            [
                'company_id'     => $companyId,
                'description'    => $description,
                'entry_date'     => $date,
                'reference_type' => 'customer',
                'reference_id'   => $customerId,
            ],
            [
                ['account_id' => $expenseAccount->id, 'debit' => $totalAmount, 'credit' => 0],
                ['account_id' => $arAccount->id,      'debit' => 0,            'credit' => $totalAmount],
            ]
        );

        $this->journalEntryService->postEntry($entry);
    }

    /**
     * Resolve the Accounts Receivable account for this company.
     * Prefers code=1130; falls back to any asset account whose name
     * contains "ذمم" or "عملاء".
     *
     * @throws \RuntimeException when no AR account can be found
     */
    private function resolveArAccount(int $companyId): Account
    {
        $account = Account::where('tenant_id', $companyId)
            ->where('type', 'asset')
            ->where(function ($q) {
                $q->where('code', '1130')
                  ->orWhere('name', 'like', '%ذمم%')
                  ->orWhere('name', 'like', '%عملاء%');
            })
            ->orderByRaw("CASE WHEN code = '1130' THEN 0 ELSE 1 END")
            ->first();

        if (! $account) {
            throw new \RuntimeException(
                'حساب الذمم المدينة (1130) غير موجود في دليل الحسابات. ' .
                'يرجى إنشاؤه قبل إجراء التسوية.'
            );
        }

        return $account;
    }

    /**
     * Find an expense account by code, or create it automatically under
     * the parent "5000 - المصروفات" if one exists.
     */
    private function resolveOrCreateExpenseAccount(int $companyId, string $code, string $name): Account
    {
        $existing = Account::where('tenant_id', $companyId)
            ->where('code', $code)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Try to nest under the main expenses parent (code 5000)
        $parent = Account::where('tenant_id', $companyId)
            ->where('code', '5000')
            ->first();

        return Account::create([
            'tenant_id'      => $companyId,
            'parent_id'      => $parent?->id,
            'code'           => $code,
            'name'           => $name,
            'type'           => 'expense',
            'normal_balance' => 'debit',
            'is_system'      => false,
            'is_active'      => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // Invoice number
    // -------------------------------------------------------------------------

    /**
     * Generate the next sequential invoice number for this company.
     * Format: INV-{year}-{4-digit-seq}  e.g. INV-2026-0001
     */
    public function nextInvoiceNumber(int $companyId): string
    {
        $year   = now()->year;
        $prefix = "INV-{$year}-";

        $last = CustomerInvoice::where('company_id', $companyId)
            ->where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // -------------------------------------------------------------------------
    // CRUD helpers
    // -------------------------------------------------------------------------

    public function createCustomer(array $data, int $companyId): Customer
    {
        return Customer::create([
            'company_id'      => $companyId,
            'name'            => $data['name'],
            'phone'           => $data['phone']           ?? null,
            'email'           => $data['email']           ?? null,
            'address'         => $data['address']         ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
        ]);
    }

    public function addInvoice(array $data, Customer $customer): CustomerInvoice
    {
        return CustomerInvoice::create([
            'company_id'     => $customer->company_id,
            'customer_id'    => $customer->id,
            'invoice_number' => $this->nextInvoiceNumber($customer->company_id),
            'description'    => $data['description'] ?? null,
            'amount'         => $data['amount'],
            'issue_date'     => $data['issue_date'],
            'due_date'       => $data['due_date'] ?? null,
        ]);
    }

    public function addPayment(array $data, Customer $customer): CustomerPayment
    {
        return CustomerPayment::create([
            'company_id'     => $customer->company_id,
            'customer_id'    => $customer->id,
            'invoice_id'     => $data['invoice_id']     ?? null,
            'payment_method' => $data['payment_method'] ?? 'cash',
            'amount'         => $data['amount'],
            'payment_date'   => $data['payment_date'],
            'notes'          => $data['notes']          ?? null,
        ]);
    }
}

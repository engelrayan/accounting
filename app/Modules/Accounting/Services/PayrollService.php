<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\Employee;
use App\Modules\Accounting\Models\PayrollLine;
use App\Modules\Accounting\Models\PayrollRun;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(
        private readonly JournalEntryService $journalEntryService,
    ) {}

    // =========================================================================
    // Next employee number
    // =========================================================================

    public function nextEmployeeNumber(int $companyId): string
    {
        $last = Employee::where('company_id', $companyId)
            ->orderByDesc('id')
            ->value('employee_number');

        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        } else {
            $seq = 1;
        }

        return 'EMP-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // =========================================================================
    // Create payroll run
    // =========================================================================

    /**
     * Create a draft payroll run for the given period and populate lines
     * from all active employees.
     *
     * @throws \DomainException  if a run already exists for this period
     */
    public function createRun(int $companyId, int $year, int $month): PayrollRun
    {
        $existing = PayrollRun::where('company_id', $companyId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();

        if ($existing) {
            throw new \DomainException(
                'يوجد مسير رواتب بالفعل لهذه الفترة: ' . $existing->periodLabel()
            );
        }

        return DB::transaction(function () use ($companyId, $year, $month) {

            $run = PayrollRun::create([
                'company_id'   => $companyId,
                'period_year'  => $year,
                'period_month' => $month,
                'status'       => 'draft',
            ]);

            $employees = Employee::forCompany($companyId)->active()->orderBy('name')->get();

            foreach ($employees as $emp) {
                $basic = (float) $emp->basic_salary;
                PayrollLine::create([
                    'payroll_run_id' => $run->id,
                    'employee_id'    => $emp->id,
                    'basic_salary'   => $basic,
                    'allowances'     => null,
                    'deductions'     => null,
                    'gross_salary'   => $basic,
                    'net_salary'     => $basic,
                    'payment_method' => 'bank',
                ]);
            }

            $this->recalculateRunTotals($run);

            return $run->fresh();
        });
    }

    // =========================================================================
    // Update a single payroll line
    // =========================================================================

    /**
     * Update allowances / deductions for one employee line and recompute totals.
     *
     * @throws \DomainException  if run is already approved
     */
    public function updateLine(PayrollLine $line, array $data): PayrollLine
    {
        if ($line->payrollRun->isApproved()) {
            throw new \DomainException('لا يمكن تعديل مسير رواتب معتمد.');
        }

        return DB::transaction(function () use ($line, $data) {

            $basic      = (float) ($data['basic_salary'] ?? $line->basic_salary);
            $allowances = $this->parseItems($data['allowances'] ?? []);
            $deductions = $this->parseItems($data['deductions'] ?? []);

            $totalAllow = collect($allowances)->sum('amount');
            $totalDeduct = collect($deductions)->sum('amount');

            $gross = $basic + $totalAllow;
            $net   = max(0, $gross - $totalDeduct);

            $line->update([
                'basic_salary'   => $basic,
                'allowances'     => $allowances ?: null,
                'deductions'     => $deductions ?: null,
                'gross_salary'   => $gross,
                'net_salary'     => $net,
                'payment_method' => $data['payment_method'] ?? $line->payment_method,
                'notes'          => $data['notes'] ?? $line->notes,
            ]);

            $this->recalculateRunTotals($line->payrollRun);

            return $line->fresh();
        });
    }

    // =========================================================================
    // Approve payroll run
    // =========================================================================

    /**
     * Approve the payroll run, post the GL entry, and mark as paid.
     *
     * GL:
     *   DR مصروف الرواتب (5300)       — total_gross
     *   CR النقدية (1110)             — net portion paid cash
     *   CR البنك (1120)               — net portion paid bank
     *   CR استحقاقات موظفين (2150)   — deductions (if any)
     *
     * @throws \DomainException
     */
    public function approve(PayrollRun $run, int $approverId): PayrollRun
    {
        if ($run->isApproved()) {
            throw new \DomainException('المسير معتمد بالفعل.');
        }

        $run->load('lines');

        if ($run->lines->isEmpty()) {
            throw new \DomainException('لا توجد بنود في المسير. أضف موظفين أولاً.');
        }

        if ((float) $run->total_net <= 0) {
            throw new \DomainException('إجمالي صافي الرواتب يجب أن يكون أكبر من صفر.');
        }

        return DB::transaction(function () use ($run, $approverId) {

            $companyId = $run->company_id;

            // Accounts
            $salaryExpense = $this->resolveOrCreateAccount($companyId, '5300', 'expense', 'مصروف الرواتب',          'debit',  '5000');
            $cashAccount   = $this->resolveOrCreateAccount($companyId, '1110', 'asset',   'الصندوق',                'debit',  '1100');
            $bankAccount   = $this->resolveOrCreateAccount($companyId, '1120', 'asset',   'البنك',                  'debit',  '1100');
            $accrualAcct   = $this->resolveOrCreateAccount($companyId, '2150', 'liability','استحقاقات الموظفين',    'credit', '2100');

            // Compute cash / bank / deductions splits from lines
            $cashNet   = 0.0;
            $bankNet   = 0.0;
            $otherNet  = 0.0;

            foreach ($run->lines as $line) {
                $net = (float) $line->net_salary;
                match($line->payment_method) {
                    'cash'  => $cashNet  += $net,
                    'bank'  => $bankNet  += $net,
                    default => $otherNet += $net,
                };
            }

            $totalDeductions = (float) $run->total_deductions;
            $totalGross      = (float) $run->total_gross;

            // Build GL lines
            $lines = [
                ['account_id' => $salaryExpense->id, 'debit' => $totalGross, 'credit' => 0],
            ];

            if ($cashNet > 0) {
                $lines[] = ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => $cashNet];
            }
            if (($bankNet + $otherNet) > 0) {
                $lines[] = ['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => $bankNet + $otherNet];
            }
            if ($totalDeductions > 0) {
                $lines[] = ['account_id' => $accrualAcct->id, 'debit' => 0, 'credit' => $totalDeductions];
            }

            $entry = $this->journalEntryService->createEntry(
                [
                    'company_id'     => $companyId,
                    'description'    => 'مسير رواتب — ' . $run->periodLabel(),
                    'entry_date'     => now()->toDateString(),
                    'reference_type' => 'payroll_run',
                    'reference_id'   => $run->id,
                ],
                $lines
            );

            $this->journalEntryService->postEntry($entry);

            $run->update([
                'status'           => 'paid',
                'journal_entry_id' => $entry->id,
                'approved_at'      => now(),
                'approved_by'      => $approverId,
            ]);

            return $run->fresh();
        });
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    private function recalculateRunTotals(PayrollRun $run): void
    {
        $run->load('lines');

        $totalBasic      = $run->lines->sum(fn ($l) => (float) $l->basic_salary);
        $totalAllowances = $run->lines->sum(fn ($l) => $l->totalAllowances());
        $totalDeductions = $run->lines->sum(fn ($l) => $l->totalDeductions());
        $totalGross      = $run->lines->sum(fn ($l) => (float) $l->gross_salary);
        $totalNet        = $run->lines->sum(fn ($l) => (float) $l->net_salary);

        $run->update([
            'total_basic'      => round($totalBasic,      2),
            'total_allowances' => round($totalAllowances, 2),
            'total_deductions' => round($totalDeductions, 2),
            'total_gross'      => round($totalGross,      2),
            'total_net'        => round($totalNet,        2),
        ]);
    }

    /**
     * Parse allowances/deductions array, filtering empty rows.
     */
    private function parseItems(array $items): array
    {
        return collect($items)
            ->filter(fn ($i) => ! empty($i['name']) && isset($i['amount']) && (float) $i['amount'] > 0)
            ->map(fn ($i) => ['name' => trim($i['name']), 'amount' => round((float) $i['amount'], 2)])
            ->values()
            ->all();
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

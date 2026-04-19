<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Accounting\Services\Reports\IncomeExpenseReport;
use App\Modules\Accounting\Services\Reports\ProfitLossReport;
use App\Modules\Accounting\Services\Reports\ReportPeriodResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountingReportExportsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-15 10:00:00');

        $this->user = User::factory()->create([
            'company_id' => 1,
            'role'       => User::ROLE_ADMIN,
        ]);

        $this->seedReportData();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_period_resolver_can_default_to_current_month(): void
    {
        $period = app(ReportPeriodResolver::class)->resolve(null, null, null, 'month');

        $this->assertSame('month', $period['period']);
        $this->assertSame('2026-04-01', $period['from']);
        $this->assertSame('2026-04-15', $period['to']);
    }

    public function test_income_expense_report_aggregates_monthly_activity(): void
    {
        $report = app(IncomeExpenseReport::class)->generate(1, '2026-04-01', '2026-04-15');

        $this->assertSame(1500.0, $report['total_income']);
        $this->assertSame(600.0, $report['total_expenses']);
        $this->assertSame(900.0, $report['net_result']);
        $this->assertCount(15, $report['series']);
    }

    public function test_profit_loss_report_calculates_net_profit(): void
    {
        $report = app(ProfitLossReport::class)->generate(1, '2026-04-01', '2026-04-15');

        $this->assertSame(1500.0, $report['total_revenue']);
        $this->assertSame(600.0, $report['total_expenses']);
        $this->assertSame(900.0, $report['net_profit']);
        $this->assertSame(60.0, $report['margin_pct']);
    }

    private function seedReportData(): void
    {
        $now = now();

        $cashAccountId = DB::table('accounts')->insertGetId([
            'tenant_id'       => 1,
            'parent_id'       => null,
            'code'            => '1110',
            'name'            => 'Cash',
            'type'            => 'asset',
            'normal_balance'  => 'debit',
            'is_system'       => false,
            'is_active'       => true,
            'description'     => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $revenueAccountId = DB::table('accounts')->insertGetId([
            'tenant_id'       => 1,
            'parent_id'       => null,
            'code'            => '4100',
            'name'            => 'Shipping Revenue',
            'type'            => 'revenue',
            'normal_balance'  => 'credit',
            'is_system'       => false,
            'is_active'       => true,
            'description'     => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $expenseAccountId = DB::table('accounts')->insertGetId([
            'tenant_id'       => 1,
            'parent_id'       => null,
            'code'            => '5100',
            'name'            => 'Salary Expense',
            'type'            => 'expense',
            'normal_balance'  => 'debit',
            'is_system'       => false,
            'is_active'       => true,
            'description'     => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $incomeEntryId = DB::table('journal_entries')->insertGetId([
            'tenant_id'      => 1,
            'entry_number'   => 'JE-2026-00001',
            'reference_type' => 'transaction',
            'reference_id'   => 1,
            'description'    => 'Income',
            'entry_date'     => '2026-04-10',
            'status'         => 'posted',
            'reversed_by'    => null,
            'reversal_of_id' => null,
            'created_by'     => $this->user->id,
            'updated_by'     => $this->user->id,
            'posted_at'      => $now,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        DB::table('journal_lines')->insert([
            [
                'journal_entry_id' => $incomeEntryId,
                'account_id'       => $cashAccountId,
                'description'      => 'Cash receipt',
                'debit'            => 1500,
                'credit'           => 0,
                'currency'         => 'SAR',
                'exchange_rate'    => 1,
            ],
            [
                'journal_entry_id' => $incomeEntryId,
                'account_id'       => $revenueAccountId,
                'description'      => 'Income booking',
                'debit'            => 0,
                'credit'           => 1500,
                'currency'         => 'SAR',
                'exchange_rate'    => 1,
            ],
        ]);

        $expenseEntryId = DB::table('journal_entries')->insertGetId([
            'tenant_id'      => 1,
            'entry_number'   => 'JE-2026-00002',
            'reference_type' => 'transaction',
            'reference_id'   => 2,
            'description'    => 'Expense',
            'entry_date'     => '2026-04-12',
            'status'         => 'posted',
            'reversed_by'    => null,
            'reversal_of_id' => null,
            'created_by'     => $this->user->id,
            'updated_by'     => $this->user->id,
            'posted_at'      => $now,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        DB::table('journal_lines')->insert([
            [
                'journal_entry_id' => $expenseEntryId,
                'account_id'       => $expenseAccountId,
                'description'      => 'Expense booking',
                'debit'            => 600,
                'credit'           => 0,
                'currency'         => 'SAR',
                'exchange_rate'    => 1,
            ],
            [
                'journal_entry_id' => $expenseEntryId,
                'account_id'       => $cashAccountId,
                'description'      => 'Cash payment',
                'debit'            => 0,
                'credit'           => 600,
                'currency'         => 'SAR',
                'exchange_rate'    => 1,
            ],
        ]);
    }
}

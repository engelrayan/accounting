<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Services\Reports\ApAgingReport;
use App\Modules\Accounting\Services\Reports\ArAgingReport;
use App\Modules\Accounting\Services\Reports\CustomerStatementReport;
use App\Modules\Accounting\Services\Reports\CashierSalesReport;
use App\Modules\Accounting\Services\Reports\IncomeExpenseReport;
use App\Modules\Accounting\Services\Reports\ProfitLossReport;
use App\Modules\Accounting\Services\Reports\ReportExportService;
use App\Modules\Accounting\Services\Reports\ReportPeriodResolver;
use App\Modules\Accounting\Services\Reports\TrialBalanceReport;
use App\Modules\Accounting\Services\Reports\VendorStatementReport;
use App\Modules\Accounting\Services\Reports\VatReport;
use App\Modules\Accounting\Models\Customer;
use App\Modules\Accounting\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly TrialBalanceReport      $trialBalanceReport,
        private readonly ProfitLossReport        $profitLossReport,
        private readonly IncomeExpenseReport     $incomeExpenseReport,
        private readonly CashierSalesReport      $cashierSalesReport,
        private readonly ArAgingReport           $arAgingReport,
        private readonly CustomerStatementReport $customerStatementReport,
        private readonly ApAgingReport           $apAgingReport,
        private readonly VendorStatementReport   $vendorStatementReport,
        private readonly VatReport               $vatReport,
        private readonly ReportPeriodResolver    $periodResolver,
        private readonly ReportExportService     $reportExport,
    ) {}

    public function incomeExpense(Request $request): View|Response
    {
        $request->validate([
            'period' => ['nullable', 'in:week,month,custom'],
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date', 'after_or_equal:from'],
            'export' => ['nullable', 'in:excel,pdf'],
        ]);

        $period = $this->periodResolver->resolve(
            $request->input('period'),
            $request->input('from'),
            $request->input('to'),
            'month',
        );

        $data = array_merge($period, $this->incomeExpenseReport->generate(
            companyId: $request->user()->company_id,
            from: $period['from'],
            to: $period['to'],
        ));

        if ($export = $request->input('export')) {
            return $this->export(
                format: $export,
                filename: $this->reportExport->filename('income-expense', $period['from'], $period['to']),
                view: 'accounting.reports.exports.income_expense',
                data: [
                    ...$data,
                    'report_title' => 'تقرير الدخل والمصروف',
                    'exported_at'  => now(),
                ],
            );
        }

        return view('accounting.reports.income_expense', $data);
    }

    public function trialBalance(Request $request): View|Response
    {
        $request->validate([
            'period'      => ['nullable', 'in:week,month,custom'],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date', 'after_or_equal:from'],
            'filter_type' => ['nullable', 'in:asset,liability,equity,revenue,expense'],
            'export'      => ['nullable', 'in:excel,pdf'],
        ]);

        $period = $this->periodResolver->resolve(
            $request->input('period'),
            $request->input('from'),
            $request->input('to'),
        );

        $data = array_merge($period, $this->trialBalanceReport->generate(
            companyId:  $request->user()->company_id,
            from:       $period['from'],
            to:         $period['to'],
            filterType: $request->input('filter_type'),
        ));

        if ($export = $request->input('export')) {
            return $this->export(
                format: $export,
                filename: $this->reportExport->filename('trial-balance', $period['from'], $period['to']),
                view: 'accounting.reports.exports.trial_balance',
                data: [
                    ...$data,
                    'report_title' => 'ميزان المراجعة',
                    'exported_at'  => now(),
                ],
                orientation: 'landscape',
            );
        }

        return view('accounting.reports.trial_balance', $data);
    }

    // Account ledger drill-down — all posted lines for one account
    public function accountLedger(Request $request, int $accountId): View
    {
        $companyId = $request->user()->company_id;

        $account = DB::table('accounts')
            ->where('id', $accountId)
            ->where('tenant_id', $companyId)
            ->first();

        abort_if(!$account, 404);

        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->input('from');
        $to   = $request->input('to');

        $lines = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('je.tenant_id', $companyId)
            ->where('je.status', 'posted')
            ->where('jl.account_id', $accountId)
            ->when($from, fn ($q) => $q->where('je.entry_date', '>=', $from))
            ->when($to,   fn ($q) => $q->where('je.entry_date', '<=', $to))
            ->orderBy('je.entry_date')
            ->orderBy('je.id')
            ->select([
                'je.entry_date',
                'je.entry_number',
                'je.description as entry_description',
                'jl.debit',
                'jl.credit',
                'jl.description as line_description',
            ])
            ->get();

        // Running balance
        $runningBalance = 0.0;
        $lines = $lines->map(function ($line) use (&$runningBalance, $account) {
            $d = (float) $line->debit;
            $c = (float) $line->credit;
            $runningBalance += ($account->normal_balance === 'debit') ? ($d - $c) : ($c - $d);
            $line->running_balance = $runningBalance;
            return $line;
        });

        $totalDebit  = (float) $lines->sum('debit');
        $totalCredit = (float) $lines->sum('credit');

        return view('accounting.reports.account_ledger', compact(
            'account', 'lines', 'totalDebit', 'totalCredit',
            'runningBalance', 'from', 'to',
        ));
    }

    public function profitLoss(Request $request): View|Response
    {
        $request->validate([
            'period' => ['nullable', 'in:week,month,custom'],
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date', 'after_or_equal:from'],
            'export' => ['nullable', 'in:excel,pdf'],
        ]);

        $period = $this->periodResolver->resolve(
            $request->input('period'),
            $request->input('from'),
            $request->input('to'),
        );

        $data = array_merge($period, $this->profitLossReport->generate(
            companyId: $request->user()->company_id,
            from:      $period['from'],
            to:        $period['to'],
        ));

        if ($export = $request->input('export')) {
            return $this->export(
                format: $export,
                filename: $this->reportExport->filename('profit-loss', $period['from'], $period['to']),
                view: 'accounting.reports.exports.profit_loss',
                data: [
                    ...$data,
                    'report_title' => 'قائمة الأرباح والخسائر',
                    'exported_at'  => now(),
                ],
            );
        }

        return view('accounting.reports.profit_loss', $data);
    }

    public function arAging(Request $request): View|Response
    {
        $request->validate([
            'as_of'  => ['nullable', 'date'],
            'export' => ['nullable', 'in:pdf'],
        ]);

        $asOf = $request->input('as_of', now()->toDateString());

        $data = $this->arAgingReport->generate(
            companyId: $request->user()->company_id,
            asOf:      $asOf,
        );

        if ($request->input('export') === 'pdf') {
            return $this->export(
                format: 'pdf',
                filename: 'ar-aging-' . $asOf,
                view: 'accounting.reports.exports.ar_aging',
                data: [
                    ...$data,
                    'report_title' => 'تقرير تقادم الذمم المدينة',
                    'exported_at'  => now(),
                ],
            );
        }

        return view('accounting.reports.ar_aging', $data);
    }

    public function cashierSales(Request $request): View
    {
        $request->validate([
            'from'       => ['nullable', 'date'],
            'to'         => ['nullable', 'date', 'after_or_equal:from'],
            'cashier_id' => ['nullable', 'integer'],
        ]);

        $from = $request->input('from', now()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        return view('accounting.reports.cashier_sales', $this->cashierSalesReport->generate(
            companyId: $request->user()->company_id,
            from: $from,
            to: $to,
            cashierId: $request->integer('cashier_id') ?: null,
        ));
    }

    public function customerStatement(Request $request, int $customerId): View|Response
    {
        $companyId = $request->user()->company_id;

        $customer = Customer::where('company_id', $companyId)->findOrFail($customerId);

        $request->validate([
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date', 'after_or_equal:from'],
            'export' => ['nullable', 'in:pdf'],
        ]);

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to',   now()->toDateString());

        $data = $this->customerStatementReport->generate($customer, $from, $to);

        if ($request->input('export') === 'pdf') {
            $filename = 'statement-' . str($customer->name)->slug() . '-' . $from . '-' . $to;
            return $this->export(
                format: 'pdf',
                filename: $filename,
                view: 'accounting.reports.exports.customer_statement',
                data: [
                    ...$data,
                    'report_title' => 'كشف حساب — ' . $customer->name,
                    'exported_at'  => now(),
                ],
            );
        }

        return view('accounting.reports.customer_statement', $data);
    }

    public function apAging(Request $request): View|Response
    {
        $request->validate([
            'as_of'  => ['nullable', 'date'],
            'export' => ['nullable', 'in:pdf'],
        ]);

        $asOf = $request->input('as_of', now()->toDateString());

        $data = $this->apAgingReport->generate(
            companyId: $request->user()->company_id,
            asOf:      $asOf,
        );

        if ($request->input('export') === 'pdf') {
            return $this->export(
                format: 'pdf',
                filename: 'ap-aging-' . $asOf,
                view: 'accounting.reports.exports.ap_aging',
                data: [
                    ...$data,
                    'report_title' => 'تقرير تقادم الذمم الدائنة',
                    'exported_at'  => now(),
                ],
            );
        }

        return view('accounting.reports.ap_aging', $data);
    }

    public function vendorStatement(Request $request, int $vendorId): View|Response
    {
        $companyId = $request->user()->company_id;

        $vendor = Vendor::where('company_id', $companyId)->findOrFail($vendorId);

        $request->validate([
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date', 'after_or_equal:from'],
            'export' => ['nullable', 'in:pdf'],
        ]);

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to',   now()->toDateString());

        $data = $this->vendorStatementReport->generate($vendor, $from, $to);

        if ($request->input('export') === 'pdf') {
            $filename = 'statement-vendor-' . str($vendor->name)->slug() . '-' . $from . '-' . $to;
            return $this->export(
                format: 'pdf',
                filename: $filename,
                view: 'accounting.reports.exports.vendor_statement',
                data: [
                    ...$data,
                    'report_title' => 'كشف حساب مورد — ' . $vendor->name,
                    'exported_at'  => now(),
                ],
            );
        }

        return view('accounting.reports.vendor_statement', $data);
    }

    public function vatReport(Request $request): View|Response
    {
        $request->validate([
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date', 'after_or_equal:from'],
            'export' => ['nullable', 'in:pdf'],
        ]);

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to',   now()->endOfMonth()->toDateString());

        $data = $this->vatReport->generate(
            companyId: $request->user()->company_id,
            from:      $from,
            to:        $to,
        );

        if ($request->input('export') === 'pdf') {
            $filename = 'vat-report-' . $from . '-' . $to;
            return $this->export(
                format: 'pdf',
                filename: $filename,
                view: 'accounting.reports.exports.vat_report',
                data: [
                    ...$data,
                    'report_title' => 'تقرير ضريبة القيمة المضافة',
                    'exported_at'  => now(),
                ],
            );
        }

        return view('accounting.reports.vat_report', $data);
    }

    private function export(
        string $format,
        string $filename,
        string $view,
        array $data,
        string $orientation = 'portrait',
    ): Response {
        return match ($format) {
            'excel' => $this->reportExport->downloadExcel($view, $data, $filename),
            'pdf'   => $this->reportExport->downloadPdf($view, $data, $filename, $orientation),
        };
    }
}

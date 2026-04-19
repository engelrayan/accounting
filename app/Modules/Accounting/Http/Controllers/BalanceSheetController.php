<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Services\Reports\BalanceSheetReport;
use App\Modules\Accounting\Services\Reports\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BalanceSheetController extends Controller
{
    public function __construct(
        private readonly BalanceSheetReport  $report,
        private readonly ReportExportService $reportExport,
    ) {}

    public function index(Request $request): View|Response
    {
        $request->validate([
            'as_of'  => ['nullable', 'date'],
            'export' => ['nullable', 'in:excel,pdf'],
        ]);

        $asOf = $request->input('as_of', today()->toDateString());

        $data = $this->report->generate(
            companyId: $request->user()->company_id,
            asOf:      $asOf,
        );

        if ($export = $request->input('export')) {
            $filename = $this->reportExport->filename('balance-sheet', $asOf, $asOf);

            return match ($export) {
                'excel' => $this->reportExport->downloadExcel(
                    'accounting.reports.exports.balance_sheet',
                    [...$data, 'report_title' => 'الميزانية العمومية', 'exported_at' => now()],
                    $filename,
                ),
                'pdf' => $this->reportExport->downloadPdf(
                    'accounting.reports.exports.balance_sheet',
                    [...$data, 'report_title' => 'الميزانية العمومية', 'exported_at' => now()],
                    $filename,
                ),
            };
        }

        return view('admin.accounting.reports.balance-sheet', $data);
    }
}

<?php

namespace App\Modules\Accounting\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApAgingReport
{
    /**
     * Generate AP Aging Report as of a given date.
     *
     * Buckets (same as AR aging):
     *   current — due_date >= asOf (not yet overdue)
     *   b1_30   — 1 – 30 days past due
     *   b31_60  — 31 – 60 days past due
     *   b61_90  — 61 – 90 days past due
     *   b91plus — 91+ days past due
     *
     * @return array{
     *   rows: Collection,
     *   totals: object,
     *   as_of: string,
     *   total_outstanding: float,
     *   total_overdue: float,
     *   vendor_count: int,
     *   max_days_overdue: int,
     * }
     */
    public function generate(int $companyId, string $asOf): array
    {
        $asOfDate = Carbon::parse($asOf)->startOfDay();

        $invoices = DB::table('purchase_invoices as pi')
            ->join('vendors as v', 'v.id', '=', 'pi.vendor_id')
            ->where('pi.company_id', $companyId)
            ->where('pi.status', '!=', 'cancelled')
            ->where(DB::raw('CAST(pi.remaining_amount AS DECIMAL(15,2))'), '>', 0)
            ->where('pi.issue_date', '<=', $asOf)
            ->select([
                'v.id as vendor_id',
                'v.name as vendor_name',
                'pi.id as invoice_id',
                'pi.invoice_number',
                'pi.due_date',
                'pi.remaining_amount',
            ])
            ->orderBy('v.name')
            ->orderBy('pi.due_date')
            ->get();

        // ── Per-vendor bucketing ────────────────────────────────────────────
        $byVendor = $invoices->groupBy('vendor_id');

        $rows = $byVendor->map(function (Collection $vendorInvoices, int $vendorId) use ($asOfDate): object {
            $row = (object) [
                'vendor_id'     => $vendorId,
                'vendor_name'   => $vendorInvoices->first()->vendor_name,
                'current'       => 0.0,
                'b1_30'         => 0.0,
                'b31_60'        => 0.0,
                'b61_90'        => 0.0,
                'b91plus'       => 0.0,
                'total'         => 0.0,
                'invoice_count' => $vendorInvoices->count(),
                'worst_days'    => 0,
            ];

            foreach ($vendorInvoices as $inv) {
                $amount  = (float) $inv->remaining_amount;
                $dueDate = Carbon::parse($inv->due_date)->startOfDay();

                if ($asOfDate->lessThanOrEqualTo($dueDate)) {
                    $row->current += $amount;
                } else {
                    $daysOverdue = (int) $asOfDate->diffInDays($dueDate);
                    $row->worst_days = max($row->worst_days, $daysOverdue);

                    if ($daysOverdue <= 30)      $row->b1_30   += $amount;
                    elseif ($daysOverdue <= 60)  $row->b31_60  += $amount;
                    elseif ($daysOverdue <= 90)  $row->b61_90  += $amount;
                    else                         $row->b91plus += $amount;
                }

                $row->total += $amount;
            }

            $row->current = round($row->current, 2);
            $row->b1_30   = round($row->b1_30, 2);
            $row->b31_60  = round($row->b31_60, 2);
            $row->b61_90  = round($row->b61_90, 2);
            $row->b91plus = round($row->b91plus, 2);
            $row->total   = round($row->total, 2);

            return $row;
        })->sortByDesc('total')->values();

        // ── Grand totals ─────────────────────────────────────────────────────
        $totals = (object) [
            'current' => round((float) $rows->sum('current'), 2),
            'b1_30'   => round((float) $rows->sum('b1_30'), 2),
            'b31_60'  => round((float) $rows->sum('b31_60'), 2),
            'b61_90'  => round((float) $rows->sum('b61_90'), 2),
            'b91plus' => round((float) $rows->sum('b91plus'), 2),
            'total'   => round((float) $rows->sum('total'), 2),
        ];

        $totalOverdue   = round($totals->b1_30 + $totals->b31_60 + $totals->b61_90 + $totals->b91plus, 2);
        $maxDaysOverdue = (int) $rows->max('worst_days');

        return [
            'rows'              => $rows,
            'totals'            => $totals,
            'as_of'             => $asOf,
            'total_outstanding' => $totals->total,
            'total_overdue'     => $totalOverdue,
            'vendor_count'      => $rows->count(),
            'max_days_overdue'  => $maxDaysOverdue,
        ];
    }
}

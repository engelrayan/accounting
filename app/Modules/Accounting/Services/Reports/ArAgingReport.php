<?php

namespace App\Modules\Accounting\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ArAgingReport
{
    /**
     * Generate AR Aging Report as of a given date.
     *
     * Buckets:
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
     *   customer_count: int,
     *   max_days_overdue: int,
     * }
     */
    public function generate(int $companyId, string $asOf): array
    {
        $asOfDate = Carbon::parse($asOf)->startOfDay();

        $invoices = DB::table('invoices as inv')
            ->join('customers as c', 'c.id', '=', 'inv.customer_id')
            ->where('inv.company_id', $companyId)
            ->where('inv.status', '!=', 'cancelled')
            ->where(DB::raw('CAST(inv.remaining_amount AS DECIMAL(15,2))'), '>', 0)
            ->where('inv.issue_date', '<=', $asOf)
            ->select([
                'c.id as customer_id',
                'c.name as customer_name',
                'inv.id as invoice_id',
                'inv.invoice_number',
                'inv.due_date',
                'inv.remaining_amount',
            ])
            ->orderBy('c.name')
            ->orderBy('inv.due_date')
            ->get();

        // ── Per-customer bucketing ──────────────────────────────────────────
        $byCustomer = $invoices->groupBy('customer_id');

        $rows = $byCustomer->map(function (Collection $customerInvoices, int $customerId) use ($asOfDate): object {
            $row = (object) [
                'customer_id'   => $customerId,
                'customer_name' => $customerInvoices->first()->customer_name,
                'current'       => 0.0,
                'b1_30'         => 0.0,
                'b31_60'        => 0.0,
                'b61_90'        => 0.0,
                'b91plus'       => 0.0,
                'total'         => 0.0,
                'invoice_count' => $customerInvoices->count(),
                'worst_days'    => 0,   // highest days overdue for this customer
            ];

            foreach ($customerInvoices as $inv) {
                $amount  = (float) $inv->remaining_amount;
                $dueDate = Carbon::parse($inv->due_date)->startOfDay();

                if ($asOfDate->lessThanOrEqualTo($dueDate)) {
                    // Not yet past due — current bucket
                    $row->current += $amount;
                } else {
                    $daysOverdue = (int) $asOfDate->diffInDays($dueDate);
                    $row->worst_days = max($row->worst_days, $daysOverdue);

                    if ($daysOverdue <= 30) {
                        $row->b1_30 += $amount;
                    } elseif ($daysOverdue <= 60) {
                        $row->b31_60 += $amount;
                    } elseif ($daysOverdue <= 90) {
                        $row->b61_90 += $amount;
                    } else {
                        $row->b91plus += $amount;
                    }
                }

                $row->total += $amount;
            }

            // Round all buckets
            $row->current = round($row->current, 2);
            $row->b1_30   = round($row->b1_30, 2);
            $row->b31_60  = round($row->b31_60, 2);
            $row->b61_90  = round($row->b61_90, 2);
            $row->b91plus = round($row->b91plus, 2);
            $row->total   = round($row->total, 2);

            return $row;
        })->sortByDesc('total')->values();

        // ── Grand totals ────────────────────────────────────────────────────
        $totals = (object) [
            'current' => round((float) $rows->sum('current'), 2),
            'b1_30'   => round((float) $rows->sum('b1_30'), 2),
            'b31_60'  => round((float) $rows->sum('b31_60'), 2),
            'b61_90'  => round((float) $rows->sum('b61_90'), 2),
            'b91plus' => round((float) $rows->sum('b91plus'), 2),
            'total'   => round((float) $rows->sum('total'), 2),
        ];

        $totalOverdue    = round($totals->b1_30 + $totals->b31_60 + $totals->b61_90 + $totals->b91plus, 2);
        $maxDaysOverdue  = (int) $rows->max('worst_days');

        return [
            'rows'              => $rows,
            'totals'            => $totals,
            'as_of'             => $asOf,
            'total_outstanding' => $totals->total,
            'total_overdue'     => $totalOverdue,
            'customer_count'    => $rows->count(),
            'max_days_overdue'  => $maxDaysOverdue,
        ];
    }
}

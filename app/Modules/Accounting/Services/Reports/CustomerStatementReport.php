<?php

namespace App\Modules\Accounting\Services\Reports;

use App\Modules\Accounting\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerStatementReport
{
    /**
     * Build a customer account statement for the given date range.
     *
     * Opening balance = opening_balance field
     *                 + all invoices issued before $from
     *                 – all payments received before $from
     *
     * Each transaction row:
     *   date        — event date
     *   type        — 'invoice' | 'payment'
     *   reference   — invoice number or "دفعة #{id}"
     *   description — notes / payment notes
     *   debit       — invoice amount (increases what customer owes)
     *   credit      — payment amount (reduces what customer owes)
     *   balance     — running balance after this row
     *
     * @return array{
     *   customer: Customer,
     *   from: string,
     *   to: string,
     *   opening_balance: float,
     *   closing_balance: float,
     *   transactions: Collection,
     *   total_debit: float,
     *   total_credit: float,
     * }
     */
    public function generate(Customer $customer, string $from, string $to): array
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate   = Carbon::parse($to)->endOfDay();

        // ── Opening balance (everything before $from) ───────────────────────
        $invoicesBefore = DB::table('invoices')
            ->where('company_id', $customer->company_id)
            ->where('customer_id', $customer->id)
            ->where('status', '!=', 'cancelled')
            ->where('issue_date', '<', $from)
            ->sum('amount');

        $paymentsBefore = DB::table('payments as p')
            ->join('invoices as inv', 'inv.id', '=', 'p.invoice_id')
            ->where('inv.company_id', $customer->company_id)
            ->where('inv.customer_id', $customer->id)
            ->where('p.payment_date', '<', $from)
            ->sum('p.amount');

        $openingBalance = round(
            (float) $customer->opening_balance
            + (float) $invoicesBefore
            - (float) $paymentsBefore,
            2
        );

        // ── Invoices within range ───────────────────────────────────────────
        $invoices = DB::table('invoices')
            ->where('company_id', $customer->company_id)
            ->where('customer_id', $customer->id)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('issue_date', [$from, $to])
            ->orderBy('issue_date')
            ->orderBy('id')
            ->select([
                'id',
                DB::raw("'invoice' as type"),
                'invoice_number as reference',
                'notes as description',
                'issue_date as event_date',
                'amount as debit',
                DB::raw('0 as credit'),
            ])
            ->get();

        // ── Payments within range ───────────────────────────────────────────
        $payments = DB::table('payments as p')
            ->join('invoices as inv', 'inv.id', '=', 'p.invoice_id')
            ->where('inv.company_id', $customer->company_id)
            ->where('inv.customer_id', $customer->id)
            ->whereBetween('p.payment_date', [$from, $to])
            ->orderBy('p.payment_date')
            ->orderBy('p.id')
            ->select([
                'p.id',
                DB::raw("'payment' as type"),
                DB::raw("CONCAT('دفعة — ', inv.invoice_number) as reference"),
                'p.notes as description',
                'p.payment_date as event_date',
                DB::raw('0 as debit'),
                'p.amount as credit',
            ])
            ->get();

        // ── Merge, sort, compute running balance ────────────────────────────
        $merged = $invoices->concat($payments)
            ->sortBy([
                ['event_date', 'asc'],
                ['type',       'desc'],   // payments after invoices on same day
            ])
            ->values();

        $runningBalance = $openingBalance;

        $transactions = $merged->map(function ($row) use (&$runningBalance): object {
            $debit  = (float) $row->debit;
            $credit = (float) $row->credit;
            $runningBalance = round($runningBalance + $debit - $credit, 2);

            return (object) [
                'type'        => $row->type,
                'reference'   => $row->reference,
                'description' => $row->description,
                'event_date'  => $row->event_date,
                'debit'       => $debit,
                'credit'      => $credit,
                'balance'     => $runningBalance,
            ];
        });

        $totalDebit  = round((float) $transactions->sum('debit'), 2);
        $totalCredit = round((float) $transactions->sum('credit'), 2);
        $closingBalance = $runningBalance;

        return [
            'customer'        => $customer,
            'from'            => $from,
            'to'              => $to,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'transactions'    => $transactions,
            'total_debit'     => $totalDebit,
            'total_credit'    => $totalCredit,
        ];
    }
}

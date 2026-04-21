<?php

namespace App\Modules\Accounting\Services\Reports;

use App\Modules\Accounting\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VendorStatementReport
{
    /**
     * Build a vendor account statement for the given date range.
     *
     * Opening balance = opening_balance field
     *                 + all purchase invoices issued before $from
     *                 – all payments made before $from
     *
     * Each row:
     *   date        — event date
     *   type        — 'invoice' | 'payment'
     *   reference   — bill number or "دفعة #{id}"
     *   description — notes
     *   debit       — payment amount (reduces what we owe)
     *   credit      — invoice amount (increases what we owe)
     *   balance     — running balance after this row
     *
     * Positive balance → we still owe the vendor.
     *
     * @return array{
     *   vendor: Vendor,
     *   from: string,
     *   to: string,
     *   opening_balance: float,
     *   closing_balance: float,
     *   transactions: Collection,
     *   total_debit: float,
     *   total_credit: float,
     * }
     */
    public function generate(Vendor $vendor, string $from, string $to): array
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate   = Carbon::parse($to)->endOfDay();

        // ── Opening balance (everything before $from) ───────────────────────
        $invoicesBefore = DB::table('purchase_invoices')
            ->where('company_id', $vendor->company_id)
            ->where('vendor_id', $vendor->id)
            ->where('status', '!=', 'cancelled')
            ->where('issue_date', '<', $from)
            ->sum('amount');

        $paymentsBefore = DB::table('purchase_payments as pp')
            ->where('pp.company_id', $vendor->company_id)
            ->where('pp.vendor_id', $vendor->id)
            ->where('pp.payment_date', '<', $from)
            ->sum('pp.amount');

        $openingBalance = round(
            (float) $vendor->opening_balance
            + (float) $invoicesBefore
            - (float) $paymentsBefore,
            2
        );

        // ── Invoices within range ───────────────────────────────────────────
        $invoices = DB::table('purchase_invoices')
            ->where('company_id', $vendor->company_id)
            ->where('vendor_id', $vendor->id)
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
                DB::raw('0 as debit'),
                'amount as credit',      // invoice = what we owe vendor (credit)
            ])
            ->get();

        // ── Payments within range ───────────────────────────────────────────
        $payments = DB::table('purchase_payments as pp')
            ->leftJoin('purchase_invoices as pi', 'pi.id', '=', 'pp.purchase_invoice_id')
            ->where('pp.company_id', $vendor->company_id)
            ->where('pp.vendor_id', $vendor->id)
            ->whereBetween('pp.payment_date', [$from, $to])
            ->orderBy('pp.payment_date')
            ->orderBy('pp.id')
            ->select([
                'pp.id',
                DB::raw("'payment' as type"),
                DB::raw("CASE WHEN pi.invoice_number IS NULL THEN CONCAT('دفعة حساب مورد #', pp.id) ELSE CONCAT('دفعة — ', pi.invoice_number) END as reference"),
                'pp.notes as description',
                'pp.payment_date as event_date',
                'pp.amount as debit',    // payment = reducing what we owe (debit)
                DB::raw('0 as credit'),
            ])
            ->get();

        // ── Merge, sort, compute running balance ────────────────────────────
        $merged = $invoices->concat($payments)
            ->sortBy([
                ['event_date', 'asc'],
                ['type',       'asc'],   // invoices before payments on same day
            ])
            ->values();

        $runningBalance = $openingBalance;

        $transactions = $merged->map(function ($row) use (&$runningBalance): object {
            $debit  = (float) $row->debit;
            $credit = (float) $row->credit;
            $runningBalance = round($runningBalance + $credit - $debit, 2);

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

        $totalDebit     = round((float) $transactions->sum('debit'), 2);
        $totalCredit    = round((float) $transactions->sum('credit'), 2);
        $closingBalance = $runningBalance;

        return [
            'vendor'          => $vendor,
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

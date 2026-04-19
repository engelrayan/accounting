<?php

namespace App\Modules\Accounting\Services\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VatReport
{
    /**
     * Generate VAT report data for the given period.
     *
     * Returns:
     *   output_vat       float  — total tax from sales invoices
     *   input_vat        float  — total tax from purchase invoices
     *   net_vat          float  — output − input (positive = payable, negative = refundable)
     *   sales_invoices   Collection
     *   purchase_invoices Collection
     *   from             string
     *   to               string
     */
    public function generate(int $companyId, string $from, string $to): array
    {
        $salesInvoices = DB::table('invoices as i')
            ->join('customers as c', 'c.id', '=', 'i.customer_id')
            ->where('i.company_id', $companyId)
            ->where('i.status', '!=', 'cancelled')
            ->whereBetween('i.issue_date', [$from, $to])
            ->where('i.tax_amount', '>', 0)
            ->orderBy('i.issue_date')
            ->orderBy('i.id')
            ->select([
                'i.id',
                'i.invoice_number',
                'i.issue_date',
                'i.status',
                'i.subtotal',
                'i.tax_rate',
                'i.tax_amount',
                'i.amount',
                'c.name as party_name',
            ])
            ->get();

        $purchaseInvoices = DB::table('purchase_invoices as pi')
            ->join('vendors as v', 'v.id', '=', 'pi.vendor_id')
            ->where('pi.company_id', $companyId)
            ->where('pi.status', '!=', 'cancelled')
            ->whereBetween('pi.issue_date', [$from, $to])
            ->where('pi.tax_amount', '>', 0)
            ->orderBy('pi.issue_date')
            ->orderBy('pi.id')
            ->select([
                'pi.id',
                'pi.invoice_number',
                'pi.issue_date',
                'pi.status',
                'pi.subtotal',
                'pi.tax_rate',
                'pi.tax_amount',
                'pi.amount',
                'v.name as party_name',
            ])
            ->get();

        $outputVat = (float) $salesInvoices->sum('tax_amount');
        $inputVat  = (float) $purchaseInvoices->sum('tax_amount');
        $netVat    = $outputVat - $inputVat;

        return compact(
            'outputVat', 'inputVat', 'netVat',
            'salesInvoices', 'purchaseInvoices',
            'from', 'to',
        );
    }
}

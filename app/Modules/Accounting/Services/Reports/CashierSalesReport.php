<?php

namespace App\Modules\Accounting\Services\Reports;

use App\Models\User;
use App\Modules\Accounting\Models\Invoice;
use Illuminate\Support\Collection;

class CashierSalesReport
{
    public function generate(int $companyId, string $from, string $to, ?int $cashierId = null): array
    {
        $query = Invoice::query()
            ->forCompany($companyId)
            ->where('source', 'pos')
            ->whereBetween('issue_date', [$from, $to])
            ->with(['customer:id,name', 'creator:id,name'])
            ->orderByDesc('issue_date')
            ->orderByDesc('id');

        if ($cashierId) {
            $query->where('created_by', $cashierId);
        }

        $sales = $query->get();

        $cashiers = User::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'from'             => $from,
            'to'               => $to,
            'cashier_id'       => $cashierId,
            'cashiers'         => $cashiers,
            'sales'            => $sales,
            'summary'          => $this->buildSummary($sales),
            'by_cashier'       => $this->groupByCashier($sales),
            'payment_mix'      => $this->paymentMix($sales),
        ];
    }

    private function buildSummary(Collection $sales): array
    {
        return [
            'count'           => $sales->count(),
            'gross'           => (float) $sales->sum('subtotal'),
            'discounts'       => (float) $sales->sum('discount_amount'),
            'net'             => (float) $sales->sum('amount'),
            'paid'            => (float) $sales->sum('paid_amount'),
            'remaining'       => (float) $sales->sum('remaining_amount'),
        ];
    }

    private function groupByCashier(Collection $sales): Collection
    {
        return $sales
            ->groupBy(fn (Invoice $invoice) => $invoice->creator?->name ?? 'غير محدد')
            ->map(function (Collection $items, string $name) {
                return [
                    'cashier'   => $name,
                    'count'     => $items->count(),
                    'gross'     => (float) $items->sum('subtotal'),
                    'discounts' => (float) $items->sum('discount_amount'),
                    'net'       => (float) $items->sum('amount'),
                ];
            })
            ->values();
    }

    private function paymentMix(Collection $sales): Collection
    {
        return $sales
            ->groupBy(fn (Invoice $invoice) => $invoice->paymentMethodLabel())
            ->map(function (Collection $items, string $method) {
                return [
                    'method' => $method,
                    'count'  => $items->count(),
                    'total'  => (float) $items->sum('paid_amount'),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }
}

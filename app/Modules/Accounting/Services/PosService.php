<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Customer;
use App\Modules\Accounting\Models\InventoryItem;
use App\Modules\Accounting\Models\Invoice;
use App\Modules\Accounting\Models\Product;
use DomainException;

class PosService
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly InventoryService $inventoryService,
    ) {}

    public function checkout(array $data, int $companyId): Invoice
    {
        $warehouse = $this->inventoryService->getDefaultWarehouse($companyId);
        $customer  = $this->resolveCustomer($companyId, $data['customer_id'] ?? null);
        $items     = [];

        foreach ($data['items'] as $item) {
            $product = Product::forCompany($companyId)
                ->active()
                ->find($item['product_id']);

            if (! $product) {
                throw new DomainException('أحد العناصر المحددة غير موجود أو غير متاح للبيع.');
            }

            $quantity = round((float) $item['quantity'], 3);
            $unitPrice = round((float) ($item['unit_price'] ?? $product->sale_price), 2);

            if ($quantity <= 0) {
                throw new DomainException('كمية البيع يجب أن تكون أكبر من صفر.');
            }

            if ($product->isProduct()) {
                $onHand = (float) InventoryItem::query()
                    ->where('product_id', $product->id)
                    ->where('warehouse_id', $warehouse->id)
                    ->value('quantity_on_hand');

                if ($quantity > $onHand + 0.0001) {
                    throw new DomainException("الكمية المتاحة من [{$product->name}] غير كافية. المتاح حاليًا: {$onHand}.");
                }
            }

            $items[] = [
                'product_id'   => $product->id,
                'description'  => $product->name,
                'quantity'     => $quantity,
                'unit_price'   => $unitPrice,
            ];
        }

        $invoice = $this->invoiceService->create([
            'customer_id'    => $customer->id,
            'issue_date'     => $data['issue_date'],
            'due_date'       => ($data['sale_mode'] ?? 'paid') === 'pending'
                ? ($data['due_date'] ?? $data['issue_date'])
                : $data['issue_date'],
            'payment_method' => $data['payment_method'] ?? null,
            'source'         => 'pos',
            'discount_amount'=> $data['discount_amount'] ?? 0,
            'notes'          => $data['notes'] ?? null,
            'items'          => $items,
        ], $companyId);

        if (($data['sale_mode'] ?? 'paid') === 'paid') {
            $this->invoiceService->recordPayment($invoice, [
                'amount'         => (float) $invoice->amount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_date'   => $data['issue_date'],
                'notes'          => 'دفعة فورية من نقطة البيع',
            ]);

            $invoice->refresh();
        }

        return $invoice;
    }

    public function walkInCustomer(int $companyId): Customer
    {
        return Customer::firstOrCreate(
            [
                'company_id' => $companyId,
                'name'       => 'عميل نقدي',
            ],
            [
                'phone'           => null,
                'email'           => null,
                'address'         => null,
                'opening_balance' => 0,
            ]
        );
    }

    private function resolveCustomer(int $companyId, mixed $customerId): Customer
    {
        if ($customerId) {
            $customer = Customer::forCompany($companyId)->find($customerId);

            if (! $customer) {
                throw new DomainException('العميل المحدد غير موجود داخل هذه الشركة.');
            }

            return $customer;
        }

        return $this->walkInCustomer($companyId);
    }
}

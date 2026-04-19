<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\InventoryItem;
use App\Modules\Accounting\Models\InventoryMovement;
use App\Modules\Accounting\Models\Product;
use App\Modules\Accounting\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(
        private readonly JournalEntryService $journalEntryService,
    ) {}

    // =========================================================================
    // Warehouse helpers
    // =========================================================================

    /**
     * Get (or create) the default warehouse for a company.
     */
    public function getDefaultWarehouse(int $companyId): Warehouse
    {
        $warehouse = Warehouse::where('company_id', $companyId)
            ->where('is_default', true)
            ->first();

        if (! $warehouse) {
            $warehouse = Warehouse::create([
                'company_id' => $companyId,
                'name'       => 'المستودع الرئيسي',
                'is_default' => true,
                'is_active'  => true,
            ]);
        }

        return $warehouse;
    }

    // =========================================================================
    // Core movement recorders
    // =========================================================================

    /**
     * Record incoming stock from a purchase.
     * Updates quantity_on_hand and recalculates weighted average cost.
     * GL: handled by PurchaseInvoiceService (DR 1300 / CR 2110).
     */
    public function recordPurchase(
        Product   $product,
        Warehouse $warehouse,
        float     $qty,
        float     $unitCost,
        string    $referenceType,
        int       $referenceId,
        int       $createdBy
    ): void {
        DB::transaction(function () use ($product, $warehouse, $qty, $unitCost, $referenceType, $referenceId, $createdBy) {

            $item = $this->getOrCreateInventoryItem($product, $warehouse);

            // Weighted average cost
            $oldQty  = (float) $item->quantity_on_hand;
            $oldCost = (float) $item->average_cost;

            $newQty = $oldQty + $qty;
            $newAvg = $newQty > 0
                ? (($oldQty * $oldCost) + ($qty * $unitCost)) / $newQty
                : $unitCost;

            $item->quantity_on_hand = $newQty;
            $item->average_cost     = round($newAvg, 4);
            $item->save();

            InventoryMovement::create([
                'company_id'     => $product->company_id,
                'product_id'     => $product->id,
                'warehouse_id'   => $warehouse->id,
                'movement_type'  => 'purchase',
                'quantity'       => $qty,
                'unit_cost'      => $unitCost,
                'total_cost'     => round($qty * $unitCost, 2),
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'created_by'     => $createdBy,
                'created_at'     => now(),
            ]);
        });
    }

    /**
     * Record outgoing stock from a sale.
     * Reduces quantity_on_hand and posts COGS GL entry.
     * GL: DR تكلفة البضاعة المباعة (5100) / CR المخزون (1300)
     */
    public function recordSale(
        Product   $product,
        Warehouse $warehouse,
        float     $qty,
        string    $referenceType,
        int       $referenceId,
        int       $createdBy,
        int       $companyId
    ): void {
        DB::transaction(function () use ($product, $warehouse, $qty, $referenceType, $referenceId, $createdBy, $companyId) {

            $item = $this->getOrCreateInventoryItem($product, $warehouse);

            $unitCost  = (float) $item->average_cost;
            $totalCost = round($qty * $unitCost, 2);

            $item->quantity_on_hand = (float) $item->quantity_on_hand - $qty;
            $item->save();

            InventoryMovement::create([
                'company_id'     => $companyId,
                'product_id'     => $product->id,
                'warehouse_id'   => $warehouse->id,
                'movement_type'  => 'sale',
                'quantity'       => -$qty,        // سالب = صادر
                'unit_cost'      => $unitCost,
                'total_cost'     => $totalCost,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'created_by'     => $createdBy,
                'created_at'     => now(),
            ]);

            // COGS entry only if there's a real cost to recognise
            if ($totalCost > 0) {
                $this->postCogsEntry($companyId, $totalCost, $referenceType, $referenceId);
            }
        });
    }

    /**
     * Manual stock adjustment (positive = add, negative = remove).
     * GL:
     *   Positive: DR 1300 / CR 5200 (فروق مخزون)
     *   Negative: DR 5200 / CR 1300
     */
    public function recordAdjustment(
        Product   $product,
        Warehouse $warehouse,
        float     $qty,          // موجب أو سالب
        float     $unitCost,     // تكلفة الوحدة للتسوية
        string    $notes,
        int       $createdBy,
        int       $companyId
    ): void {
        DB::transaction(function () use ($product, $warehouse, $qty, $unitCost, $notes, $createdBy, $companyId) {

            $item = $this->getOrCreateInventoryItem($product, $warehouse);

            if ($unitCost <= 0) {
                $unitCost = (float) $item->average_cost;
            }

            $totalCost = round(abs($qty) * $unitCost, 2);

            $item->quantity_on_hand = (float) $item->quantity_on_hand + $qty;
            // Recalculate avg cost only on positive adjustment
            if ($qty > 0) {
                $oldQty  = (float) $item->quantity_on_hand - $qty;
                $oldCost = (float) $item->average_cost;
                $newQty  = (float) $item->quantity_on_hand;
                if ($newQty > 0) {
                    $item->average_cost = round(
                        (($oldQty * $oldCost) + ($qty * $unitCost)) / $newQty,
                        4
                    );
                }
            }
            $item->save();

            InventoryMovement::create([
                'company_id'     => $companyId,
                'product_id'     => $product->id,
                'warehouse_id'   => $warehouse->id,
                'movement_type'  => 'adjustment',
                'quantity'       => $qty,
                'unit_cost'      => $unitCost,
                'total_cost'     => $totalCost,
                'reference_type' => 'adjustment',
                'reference_id'   => null,
                'notes'          => $notes,
                'created_by'     => $createdBy,
                'created_at'     => now(),
            ]);

            if ($totalCost > 0) {
                $this->postAdjustmentEntry($companyId, $qty, $totalCost);
            }
        });
    }

    // =========================================================================
    // Query helpers
    // =========================================================================

    public function getBalance(Product $product, Warehouse $warehouse): array
    {
        $item = InventoryItem::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        if (! $item) {
            return ['quantity' => 0, 'averageCost' => 0, 'totalValue' => 0];
        }

        $qty  = (float) $item->quantity_on_hand;
        $avg  = (float) $item->average_cost;

        return [
            'quantity'    => $qty,
            'averageCost' => $avg,
            'totalValue'  => round($qty * $avg, 2),
        ];
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    private function getOrCreateInventoryItem(Product $product, Warehouse $warehouse): InventoryItem
    {
        return InventoryItem::firstOrCreate(
            [
                'product_id'   => $product->id,
                'warehouse_id' => $warehouse->id,
            ],
            [
                'company_id'      => $product->company_id,
                'quantity_on_hand' => 0,
                'average_cost'    => 0,
            ]
        );
    }

    /**
     * DR تكلفة البضاعة المباعة (5100) / CR المخزون (1300)
     */
    private function postCogsEntry(int $companyId, float $amount, string $refType, int $refId): void
    {
        $inventoryAccount = $this->resolveOrCreateAccount($companyId, '1300', 'asset',   'المخزون',                      'debit',  '1000');
        $cogsAccount      = $this->resolveOrCreateAccount($companyId, '5100', 'expense', 'تكلفة البضاعة المباعة',         'debit',  '5000');

        $entry = $this->journalEntryService->createEntry(
            [
                'company_id'     => $companyId,
                'description'    => 'تكلفة البضاعة المباعة — ' . $refType . ' #' . $refId,
                'entry_date'     => now()->toDateString(),
                'reference_type' => $refType,
                'reference_id'   => $refId,
            ],
            [
                ['account_id' => $cogsAccount->id,      'debit' => $amount, 'credit' => 0],
                ['account_id' => $inventoryAccount->id, 'debit' => 0,       'credit' => $amount],
            ]
        );

        $this->journalEntryService->postEntry($entry);
    }

    /**
     * Positive adjustment: DR 1300 / CR 5200
     * Negative adjustment: DR 5200 / CR 1300
     */
    private function postAdjustmentEntry(int $companyId, float $qty, float $amount): void
    {
        $inventoryAccount   = $this->resolveOrCreateAccount($companyId, '1300', 'asset',   'المخزون',           'debit',  '1000');
        $varianceAccount    = $this->resolveOrCreateAccount($companyId, '5200', 'expense', 'فروق المخزون',       'debit',  '5000');

        if ($qty > 0) {
            // زيادة مخزون: DR 1300 / CR 5200
            $lines = [
                ['account_id' => $inventoryAccount->id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $varianceAccount->id,  'debit' => 0,       'credit' => $amount],
            ];
        } else {
            // نقص مخزون: DR 5200 / CR 1300
            $lines = [
                ['account_id' => $varianceAccount->id,  'debit' => $amount, 'credit' => 0],
                ['account_id' => $inventoryAccount->id, 'debit' => 0,       'credit' => $amount],
            ];
        }

        $entry = $this->journalEntryService->createEntry(
            [
                'company_id'     => $companyId,
                'description'    => ($qty > 0 ? 'زيادة' : 'نقص') . ' مخزون — تسوية يدوية',
                'entry_date'     => now()->toDateString(),
                'reference_type' => 'inventory_adjustment',
                'reference_id'   => null,
            ],
            $lines
        );

        $this->journalEntryService->postEntry($entry);
    }

    private function resolveOrCreateAccount(
        int    $companyId,
        string $code,
        string $type,
        string $name,
        string $normalBalance,
        string $parentCode,
    ): Account {
        $existing = Account::where('tenant_id', $companyId)->where('code', $code)->first();
        if ($existing) return $existing;

        $parent = Account::where('tenant_id', $companyId)->where('code', $parentCode)->first();

        return Account::create([
            'tenant_id'      => $companyId,
            'parent_id'      => $parent?->id,
            'code'           => $code,
            'name'           => $name,
            'type'           => $type,
            'normal_balance' => $normalBalance,
            'is_system'      => false,
            'is_active'      => true,
        ]);
    }
}

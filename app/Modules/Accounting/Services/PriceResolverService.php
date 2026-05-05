<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Customer;
use App\Modules\Accounting\Models\PriceList;
use App\Modules\Accounting\Models\PriceListItem;

/**
 * يحسب سعر التسليم والمرتجع لعميل في محافظة معينة.
 *
 * ترتيب الأولوية:
 *  1. قائمة الأسعار المخصصة للعميل (أول قائمة نشطة مرتبطة به)
 *  2. القائمة الافتراضية للشركة (is_default = true)
 *
 * إذا كانت المحافظة غير موجودة في القائمة المخصصة،
 * يتم الرجوع للقائمة الافتراضية للحصول على سعرها.
 */
class PriceResolverService
{
    /**
     * احسب السعر لعميل + محافظة.
     *
     * @return array{
     *   delivery: float|null,
     *   return: float|null,
     *   source: 'customer_list'|'default_list'|null,
     *   price_list_id: int|null,
     *   price_list_name: string|null
     * }
     */
    public function resolve(int $companyId, int $customerId, int $governorateId): array
    {
        $empty = [
            'delivery'        => null,
            'return'          => null,
            'source'          => null,
            'price_list_id'   => null,
            'price_list_name' => null,
        ];

        // ── 1. قائمة العميل المخصصة ──────────────────────────────────────────
        $customerList = PriceList::query()
            ->whereHas('customers', fn ($q) => $q->where('customer_id', $customerId))
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if ($customerList) {
            $item = PriceListItem::query()
                ->where('price_list_id', $customerList->id)
                ->where('governorate_id', $governorateId)
                ->first();

            if ($item) {
                return [
                    'delivery'        => (float) $item->price,
                    'return'          => $item->return_price !== null ? (float) $item->return_price : null,
                    'source'          => 'customer_list',
                    'price_list_id'   => $customerList->id,
                    'price_list_name' => $customerList->name,
                ];
            }
            // المحافظة غير موجودة في قائمة العميل → نرجع للافتراضية
        }

        // ── 2. القائمة الافتراضية ────────────────────────────────────────────
        $defaultList = PriceList::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if ($defaultList) {
            $item = PriceListItem::query()
                ->where('price_list_id', $defaultList->id)
                ->where('governorate_id', $governorateId)
                ->first();

            if ($item) {
                return [
                    'delivery'        => (float) $item->price,
                    'return'          => $item->return_price !== null ? (float) $item->return_price : null,
                    'source'          => 'default_list',
                    'price_list_id'   => $defaultList->id,
                    'price_list_name' => $defaultList->name,
                ];
            }
        }

        return $empty;
    }

    /**
     * احسب أسعار كل المحافظات لعميل معين.
     * يُرجع: governorate_id => resolve result
     */
    public function resolveAll(int $companyId, int $customerId): array
    {
        // جلب قائمة العميل + الافتراضية دفعة واحدة
        $customerList = PriceList::query()
            ->whereHas('customers', fn ($q) => $q->where('customer_id', $customerId))
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->with('items')
            ->first();

        $defaultList = PriceList::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->with('items')
            ->first();

        $result = [];

        // بناء map من الافتراضية أولاً
        if ($defaultList) {
            foreach ($defaultList->items as $item) {
                $result[$item->governorate_id] = [
                    'delivery'        => (float) $item->price,
                    'return'          => $item->return_price !== null ? (float) $item->return_price : null,
                    'source'          => 'default_list',
                    'price_list_id'   => $defaultList->id,
                    'price_list_name' => $defaultList->name,
                ];
            }
        }

        // override بقائمة العميل (تأخذ أولوية)
        if ($customerList) {
            foreach ($customerList->items as $item) {
                $result[$item->governorate_id] = [
                    'delivery'        => (float) $item->price,
                    'return'          => $item->return_price !== null ? (float) $item->return_price : null,
                    'source'          => 'customer_list',
                    'price_list_id'   => $customerList->id,
                    'price_list_name' => $customerList->name,
                ];
            }
        }

        return $result;
    }

    /**
     * الحصول على القائمة الافتراضية للشركة.
     */
    public function getDefault(int $companyId): ?PriceList
    {
        return PriceList::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->first();
    }

    /**
     * تعيين قائمة كافتراضية (يلغي الافتراضية السابقة).
     */
    public function setDefault(int $companyId, PriceList $priceList): void
    {
        // إلغاء الافتراضية السابقة
        PriceList::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->where('id', '!=', $priceList->id)
            ->update(['is_default' => false]);

        $priceList->update(['is_default' => true, 'is_active' => true]);
    }

    /**
     * إلغاء تعيين الافتراضية.
     */
    public function unsetDefault(int $companyId, PriceList $priceList): void
    {
        $priceList->update(['is_default' => false]);
    }
}

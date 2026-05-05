<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class)->with('governorate');
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'price_list_customers')
                    ->withTimestamps();
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): void
    {
        $query->where('is_default', true);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** العناصر كـ map: governorate_id => price (سعر التسليم) */
    public function itemsMap(): array
    {
        return $this->items
            ->pluck('price', 'governorate_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /** العناصر كـ map: governorate_id => return_price */
    public function returnPricesMap(): array
    {
        return $this->items
            ->pluck('return_price', 'governorate_id')
            ->map(fn ($v) => $v !== null ? (float) $v : null)
            ->all();
    }

    /** العناصر كـ map: governorate_id => notes */
    public function notesMap(): array
    {
        return $this->items
            ->pluck('notes', 'governorate_id')
            ->all();
    }

    /** عدد المحافظات المُسعَّرة. */
    public function governoratesCount(): int
    {
        return $this->items()->count();
    }
}

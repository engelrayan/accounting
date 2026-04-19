<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'quantity_on_hand',
        'average_cost',
        'reorder_level',
    ];

    protected $casts = [
        'quantity_on_hand' => 'decimal:3',
        'average_cost'     => 'decimal:4',
        'reorder_level'    => 'decimal:3',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function totalValue(): float
    {
        return round((float) $this->quantity_on_hand * (float) $this->average_cost, 2);
    }

    public function isBelowReorder(): bool
    {
        return $this->reorder_level !== null
            && (float) $this->quantity_on_hand <= (float) $this->reorder_level;
    }
}

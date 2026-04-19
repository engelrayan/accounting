<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'purchase_invoice_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'quantity'   => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    // ── Auto-compute total on every save ────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (PurchaseInvoiceItem $item) {
            $item->total = round((float) $item->quantity * (float) $item->unit_price, 2);
        });
    }

    // ── Relationship ─────────────────────────────────────────────────────────

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
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

    // -------------------------------------------------------------------------
    // Auto-compute total on save
    // -------------------------------------------------------------------------

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            $item->total = round((float) $item->quantity * (float) $item->unit_price, 2);
        });
    }

    // -------------------------------------------------------------------------

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

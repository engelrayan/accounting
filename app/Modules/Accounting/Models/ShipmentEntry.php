<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentEntry extends Model
{
    protected $fillable = [
        'shipment_batch_id',
        'company_id',
        'shipment_date',
        'customer_id',
        'governorate_id',
        'price_list_id',
        'price_source',
        'shipment_type',
        'quantity',
        'unit_price',
        'line_total',
        'entry_code',
        'notes',
    ];

    protected $casts = [
        'shipment_date' => 'date',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ShipmentBatch::class, 'shipment_batch_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }
}

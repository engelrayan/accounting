<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'movement_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'quantity'   => 'decimal:3',
        'unit_cost'  => 'decimal:4',
        'total_cost' => 'decimal:2',
        'created_at' => 'datetime',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
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

    public function typeLabel(): string
    {
        return match($this->movement_type) {
            'purchase'   => 'شراء',
            'sale'       => 'بيع',
            'adjustment' => 'تسوية',
            'transfer'   => 'نقل',
            'return'     => 'مرتجع',
            default      => $this->movement_type,
        };
    }

    public function typeBadgeClass(): string
    {
        return match($this->movement_type) {
            'purchase'   => 'ac-badge--info',
            'sale'       => 'ac-badge--warning',
            'adjustment' => 'ac-badge--muted',
            'return'     => 'ac-badge--success',
            default      => '',
        };
    }
}

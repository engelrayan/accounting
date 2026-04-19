<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'barcode',
        'name',
        'description',
        'type',
        'unit',
        'sale_price',
        'purchase_price',
        'tax_rate',
        'account_id',
        'is_active',
    ];

    protected $casts = [
        'sale_price'     => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'tax_rate'       => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
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

    public function scopeSearch(Builder $query, string $term): void
    {
        $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere('barcode', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function typeLabel(): string
    {
        return match($this->type) {
            'product' => 'منتج',
            'service' => 'خدمة',
            default   => $this->type,
        };
    }

    public function isProduct(): bool
    {
        return $this->type === 'product';
    }

    public function isService(): bool
    {
        return $this->type === 'service';
    }
}

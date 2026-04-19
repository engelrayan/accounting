<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Vendor extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'phone',
        'email',
        'address',
        'opening_balance',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class)->orderByDesc('issue_date');
    }

    public function purchasePayments(): HasManyThrough
    {
        return $this->hasManyThrough(
            PurchasePayment::class,
            PurchaseInvoice::class,
            'vendor_id',          // FK on purchase_invoices
            'purchase_invoice_id', // FK on purchase_payments
        )->orderByDesc('payment_date');
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

    /** First letter for avatar display. */
    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }
}

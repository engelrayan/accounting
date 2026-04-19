<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Customer extends Model
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

    /** Formal invoices (printable, with line items). */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderByDesc('issue_date');
    }

    /**
     * Payments received through formal invoices.
     * Customer → Invoice (customer_id) → Payment (invoice_id)
     */
    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payment::class,
            Invoice::class,
            'customer_id', // FK on invoices → customers
            'invoice_id',  // FK on payments → invoices
            'id',
            'id',
        )->orderByDesc('payment_date');
    }

    // Legacy (kept for backward compatibility — do not use in new code)
    public function customerInvoices(): HasMany
    {
        return $this->hasMany(CustomerInvoice::class)->orderByDesc('issue_date');
    }

    public function customerPayments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class)->orderByDesc('payment_date');
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

    /** First letter for the avatar circle. */
    public function initial(): string
    {
        return mb_substr($this->name, 0, 1);
    }
}

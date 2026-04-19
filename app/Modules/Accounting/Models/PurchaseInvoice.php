<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'company_id',
        'vendor_id',
        'invoice_number',
        'vendor_invoice_number',
        'notes',
        'payment_method',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'issue_date',
        'due_date',
    ];

    protected $casts = [
        'subtotal'         => 'decimal:2',
        'tax_rate'         => 'decimal:2',
        'tax_amount'       => 'decimal:2',
        'amount'           => 'decimal:2',
        'paid_amount'      => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'issue_date'       => 'date',
        'due_date'         => 'date',
    ];

    // -------------------------------------------------------------------------
    // Auto-sync status & remaining_amount on every save
    // -------------------------------------------------------------------------

    protected static function booted(): void
    {
        static::saving(function (PurchaseInvoice $invoice) {
            $invoice->remaining_amount = max(0, (float) $invoice->amount - (float) $invoice->paid_amount);

            $invoice->status = match (true) {
                (float) $invoice->paid_amount <= 0                       => 'pending',
                (float) $invoice->paid_amount >= (float) $invoice->amount => 'paid',
                default                                                  => 'partial',
            };
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class)->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class)->orderByDesc('payment_date');
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

    public function statusLabel(): string
    {
        return match ($this->status) {
            'paid'      => 'مدفوعة',
            'partial'   => 'جزئي',
            'cancelled' => 'ملغاة',
            default     => 'معلقة',
        };
    }

    public function statusMod(): string
    {
        return match ($this->status) {
            'paid'      => 'posted',
            'partial'   => 'draft',
            'cancelled' => 'reversed',
            default     => 'pending',
        };
    }

    public function isUnpaid(): bool
    {
        return (float) $this->remaining_amount > 0;
    }

    public function remaining(): float
    {
        return (float) $this->remaining_amount;
    }

    public function totalPaid(): float
    {
        return (float) $this->paid_amount;
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && $this->status !== 'paid'
            && $this->status !== 'cancelled';
    }

    public function paidPct(): int
    {
        $amount = (float) $this->amount;
        if ($amount <= 0) return 100;
        return (int) min(100, round((float) $this->paid_amount / $amount * 100));
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'cash'          => 'نقداً',
            'bank_transfer' => 'تحويل بنكي',
            'cheque'        => 'شيك',
            'card'          => 'بطاقة ائتمان',
            default         => $this->payment_method ?? '—',
        };
    }
}

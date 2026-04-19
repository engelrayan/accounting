<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'company_id',
        'vendor_id',
        'po_number',
        'date',
        'expected_date',
        'status',
        'notes',
        'subtotal',
        'tax_amount',
        'total',
        'purchase_invoice_id',
        'created_by',
    ];

    protected $casts = [
        'date'          => 'date',
        'expected_date' => 'date',
        'subtotal'      => 'decimal:2',
        'tax_amount'    => 'decimal:2',
        'total'         => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    // -------------------------------------------------------------------------
    // Status helpers
    // -------------------------------------------------------------------------

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isInvoiced(): bool
    {
        return $this->status === 'invoiced';
    }

    /**
     * PO can be converted to a purchase invoice when it is sent or received.
     */
    public function canConvert(): bool
    {
        return $this->isSent() || $this->isReceived();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft'     => 'مسودة',
            'sent'      => 'مرسل للمورد',
            'received'  => 'تم الاستلام',
            'cancelled' => 'ملغي',
            'invoiced'  => 'محوّل لفاتورة',
            default     => $this->status,
        };
    }

    /**
     * Returns a Bootstrap text colour class for the status badge.
     */
    public function statusColor(): string
    {
        return match ($this->status) {
            'draft'     => 'text-secondary',
            'sent'      => 'text-primary',
            'received'  => 'text-success',
            'cancelled' => 'text-danger',
            'invoiced'  => 'text-info',
            default     => 'text-secondary',
        };
    }

    /**
     * Returns a Bootstrap badge background class for the status badge.
     */
    public function statusBg(): string
    {
        return match ($this->status) {
            'draft'     => 'bg-secondary',
            'sent'      => 'bg-primary',
            'received'  => 'bg-success',
            'cancelled' => 'bg-danger',
            'invoiced'  => 'bg-info',
            default     => 'bg-secondary',
        };
    }
}

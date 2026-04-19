<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'quotation_number',
        'date',
        'valid_until',
        'status',
        'notes',
        'terms',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'invoice_id',
        'created_by',
    ];

    protected $casts = [
        'date'            => 'date',
        'valid_until'     => 'date',
        'subtotal'        => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total'           => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isInvoiced(): bool
    {
        return $this->status === 'invoiced';
    }

    public function canConvert(): bool
    {
        return in_array($this->status, ['sent', 'accepted']);
    }

    public function isExpired(): bool
    {
        return $this->valid_until
            && $this->valid_until->isPast()
            && ! in_array($this->status, ['accepted', 'invoiced', 'rejected']);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft'    => 'مسودة',
            'sent'     => 'مرسل',
            'accepted' => 'مقبول',
            'rejected' => 'مرفوض',
            'expired'  => 'منتهي',
            'invoiced' => 'محوّل لفاتورة',
            default    => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'draft'    => '#64748b',
            'sent'     => '#1d4ed8',
            'accepted' => '#15803d',
            'rejected' => '#991b1b',
            'expired'  => '#854d0e',
            'invoiced' => '#5b21b6',
            default    => '#64748b',
        };
    }

    public function statusBg(): string
    {
        return match ($this->status) {
            'draft'    => '#f1f5f9',
            'sent'     => '#dbeafe',
            'accepted' => '#dcfce7',
            'rejected' => '#fee2e2',
            'expired'  => '#fef9c3',
            'invoiced' => '#ede9fe',
            default    => '#f1f5f9',
        };
    }
}

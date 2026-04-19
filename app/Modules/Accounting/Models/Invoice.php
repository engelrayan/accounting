<?php

namespace App\Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'created_by',
        'invoice_number',
        'notes',
        'payment_method',
        'source',
        'subtotal',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'amount',
        'paid_amount',
        'credited_amount',
        'remaining_amount',
        'status',
        'issue_date',
        'due_date',
    ];

    protected $casts = [
        'subtotal'         => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'tax_rate'         => 'decimal:2',
        'tax_amount'       => 'decimal:2',
        'amount'           => 'decimal:2',
        'paid_amount'      => 'decimal:2',
        'credited_amount'  => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'issue_date'       => 'date',
        'due_date'         => 'date',
    ];

    // -------------------------------------------------------------------------
    // Auto-sync status & remaining_amount on every save
    // -------------------------------------------------------------------------

    protected static function booted(): void
    {
        static::saving(function (Invoice $invoice) {
            $settled = (float) $invoice->paid_amount + (float) $invoice->credited_amount;

            $invoice->remaining_amount = max(0, (float) $invoice->amount - $settled);

            $invoice->status = match (true) {
                $settled <= 0                             => 'pending',
                $settled >= (float) $invoice->amount     => 'paid',
                default                                   => 'partial',
            };
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('payment_date');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class)->orderByDesc('issue_date')->orderByDesc('id');
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

    /** Remaining amount owed (convenience wrapper). */
    public function remaining(): float
    {
        return (float) $this->remaining_amount;
    }

    /** Total amount paid so far (convenience wrapper). */
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

    /** Percentage settled (paid + credited) 0–100. */
    public function paidPct(): int
    {
        $amount = (float) $this->amount;
        if ($amount <= 0) return 100;
        $settled = (float) $this->paid_amount + (float) $this->credited_amount;
        return (int) min(100, round($settled / $amount * 100));
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'cash'          => 'نقداً',
            'bank'          => 'بنكي',
            'bank_transfer' => 'تحويل بنكي',
            'wallet'        => 'محفظة',
            'instapay'      => 'إنستاباي',
            'cheque'        => 'شيك',
            'card'          => 'بطاقة ائتمان',
            'other'         => 'أخرى',
            default         => $this->payment_method ?? '—',
        };
    }

    public function isPosSale(): bool
    {
        return $this->source === 'pos';
    }

    /**
     * Data encoded in the QR code — compact JSON for scanning.
     */
    public function qrData(): string
    {
        return json_encode([
            'invoice' => $this->invoice_number,
            'customer'=> $this->customer?->name ?? '',
            'amount'  => number_format((float) $this->amount, 2),
            'status'  => $this->statusLabel(),
            'date'    => $this->issue_date?->format('Y-m-d') ?? '',
        ], JSON_UNESCAPED_UNICODE);
    }
}

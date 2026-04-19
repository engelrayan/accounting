<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerInvoice extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_number',
        'description',
        'amount',
        'issue_date',
        'due_date',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'issue_date' => 'date',
        'due_date'   => 'date',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'invoice_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    // -------------------------------------------------------------------------
    // Computed helpers
    // -------------------------------------------------------------------------

    /**
     * Total amount paid against this invoice.
     * Uses the eager-loaded payments collection to avoid N+1 queries.
     */
    public function totalPaid(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount');
        }
        return (float) $this->payments()->sum('amount');
    }

    /** Amount still owed on this invoice. */
    public function remaining(): float
    {
        return max(0, (float) $this->amount - $this->totalPaid());
    }

    /**
     * Status: 'paid' | 'partial' | 'overdue' | 'pending'
     * Computed from payments + due date. Reuses totalPaid() which is cached-aware.
     */
    public function status(): string
    {
        $paid = $this->totalPaid();

        if ($paid >= (float) $this->amount) {
            return 'paid';
        }

        if ($paid > 0) {
            return 'partial';
        }

        if ($this->due_date && $this->due_date->isPast()) {
            return 'overdue';
        }

        return 'pending';
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'paid'    => 'مدفوعة',
            'partial' => 'جزئي',
            'overdue' => 'متأخرة',
            default   => 'معلقة',
        };
    }

    public function statusMod(): string
    {
        return match ($this->status()) {
            'paid'    => 'posted',
            'partial' => 'draft',
            'overdue' => 'reversed',
            default   => 'pending',
        };
    }

    /** Percentage paid (0–100). */
    public function paidPct(): int
    {
        $amount = (float) $this->amount;
        if ($amount <= 0) return 100;
        return (int) min(100, round($this->totalPaid() / $amount * 100));
    }

    /** True when the invoice has any unpaid amount and is not cancelled. */
    public function isUnpaid(): bool
    {
        return $this->remaining() > 0;
    }

    /** True when past due date and not fully paid. */
    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && $this->status() !== 'paid';
    }
}

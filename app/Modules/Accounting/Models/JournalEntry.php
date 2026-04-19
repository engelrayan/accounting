<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class JournalEntry extends Model
{
    protected $fillable = [
        'tenant_id',
        'entry_number',
        'reference_type',
        'reference_id',
        'description',
        'entry_date',
        'status',
        'reversed_by',
        'reversal_of_id',
        'created_by',
        'updated_by',
        'posted_at',
        'auto_generated',
    ];

    protected $casts = [
        'entry_date'    => 'date',
        'posted_at'     => 'datetime',
        'auto_generated'=> 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Type labels & badges (reference_type → display)
    // -------------------------------------------------------------------------

    public static function typeLabel(?string $referenceType): string
    {
        return match ($referenceType) {
            'invoice'          => 'فاتورة',
            'payment'          => 'دفعة عميل',
            'purchase_invoice' => 'فاتورة مشتريات',
            'purchase_payment' => 'دفعة مورد',
            'credit_note'      => 'إشعار دائن',
            'expense'          => 'مصروف',
            'asset'            => 'أصل ثابت',
            'settlement'       => 'تسوية',
            'opening_balance'  => 'أرصدة افتتاحية',
            'fiscal_close'     => 'إقفال سنوي',
            default            => 'عملية نظامية',
        };
    }

    public static function typeEmoji(?string $referenceType): string
    {
        return match ($referenceType) {
            'invoice'          => '🧾',
            'payment'          => '💰',
            'purchase_invoice' => '🛒',
            'purchase_payment' => '💳',
            'credit_note'      => '📋',
            'expense'          => '📉',
            'asset'            => '🏢',
            'settlement'       => '✅',
            'opening_balance'  => '🏁',
            'fiscal_close'     => '🔒',
            default            => '⚙️',
        };
    }

    public static function typeMod(?string $referenceType): string
    {
        return match ($referenceType) {
            'invoice', 'credit_note'             => 'posted',
            'payment', 'settlement'              => 'draft',
            'purchase_invoice','purchase_payment'=> 'pending',
            'expense', 'asset'                   => 'reversed',
            default                              => 'pending',
        };
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** The reversal entry that was created to cancel this one. */
    public function reversingEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversed_by');
    }

    /** The original entry that this entry is reversing. */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_of_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePosted(Builder $query): void
    {
        $query->where('status', 'posted');
    }

    public function scopeDraft(Builder $query): void
    {
        $query->where('status', 'draft');
    }

    public function scopeForTenant(Builder $query, int $tenantId): void
    {
        $query->where('tenant_id', $tenantId);
    }

    public function scopeForPeriod(Builder $query, int $year, int $month): void
    {
        $query->whereYear('entry_date', $year)
              ->whereMonth('entry_date', $month);
    }

    // -------------------------------------------------------------------------
    // Status helpers
    // -------------------------------------------------------------------------

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    // -------------------------------------------------------------------------
    // Lock guard
    // -------------------------------------------------------------------------

    /**
     * Posted and reversed entries are immutable.
     * Call this before any mutation outside of the service layer.
     */
    public function canBeModified(): bool
    {
        return $this->isDraft();
    }

    // -------------------------------------------------------------------------
    // Balance check
    // -------------------------------------------------------------------------

    /**
     * Uses bccomp to avoid floating-point rounding errors.
     */
    public function isBalanced(): bool
    {
        $totals = $this->lines()
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        if (! $totals || $totals->total_debit === null) {
            return false;
        }

        return bccomp(
            (string) $totals->total_debit,
            (string) $totals->total_credit,
            2
        ) === 0;
    }
}

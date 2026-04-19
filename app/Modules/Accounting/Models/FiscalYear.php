<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalYear extends Model
{
    protected $fillable = [
        'company_id',
        'year',
        'starts_at',
        'ends_at',
        'status',
        'net_profit',
        'closing_entry_id',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'starts_at'  => 'date',
        'ends_at'    => 'date',
        'closed_at'  => 'datetime',
        'net_profit' => 'float',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function closingEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'closing_entry_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    public function scopeOpen(Builder $query): void
    {
        $query->where('status', 'open');
    }

    public function scopeClosed(Builder $query): void
    {
        $query->where('status', 'closed');
    }

    // -------------------------------------------------------------------------
    // Status helpers
    // -------------------------------------------------------------------------

    public function isOpen(): bool   { return $this->status === 'open';   }
    public function isClosed(): bool { return $this->status === 'closed'; }

    // -------------------------------------------------------------------------
    // Period lock — used by JournalEntryService
    // -------------------------------------------------------------------------

    /**
     * Returns true if the given date falls within a closed fiscal year for this company.
     */
    public static function isDateLocked(int $companyId, string $date): bool
    {
        return static::where('company_id', $companyId)
            ->where('status', 'closed')
            ->where('starts_at', '<=', $date)
            ->where('ends_at', '>=', $date)
            ->exists();
    }

    // -------------------------------------------------------------------------
    // Factory helper
    // -------------------------------------------------------------------------

    /**
     * Return or create the fiscal year record for the given company + year.
     * Defaults to Jan 1 – Dec 31 (Gregorian) for the given year.
     */
    public static function findOrCreateForCompany(int $companyId, int $year): self
    {
        return static::firstOrCreate(
            ['company_id' => $companyId, 'year' => $year],
            [
                'starts_at' => "{$year}-01-01",
                'ends_at'   => "{$year}-12-31",
                'status'    => 'open',
            ]
        );
    }
}

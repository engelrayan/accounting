<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'description',
        'debit',
        'credit',
        'currency',
        'exchange_rate',
    ];

    protected $casts = [
        'debit'         => 'decimal:2',
        'credit'        => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Net amount in base currency: positive = debit, negative = credit. */
    public function netAmount(): float
    {
        return ((float) $this->debit - (float) $this->credit) * (float) $this->exchange_rate;
    }

    public function isDebit(): bool
    {
        return (float) $this->debit > 0;
    }

    public function isCredit(): bool
    {
        return (float) $this->credit > 0;
    }
}

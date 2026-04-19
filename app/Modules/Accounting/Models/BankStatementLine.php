<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementLine extends Model
{
    protected $fillable = [
        'bank_statement_id',
        'transaction_date',
        'description',
        'debit',
        'credit',
        'is_matched',
        'journal_line_id',
    ];

    protected $casts = [
        'debit'            => 'decimal:2',
        'credit'           => 'decimal:2',
        'is_matched'       => 'boolean',
        'transaction_date' => 'date',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    public function journalLine(): BelongsTo
    {
        return $this->belongsTo(JournalLine::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Net amount: positive = money-in (credit), negative = money-out (debit). */
    public function net(): float
    {
        return (float) $this->credit - (float) $this->debit;
    }
}

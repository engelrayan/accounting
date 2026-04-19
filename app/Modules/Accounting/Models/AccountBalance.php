<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBalance extends Model
{
    public $timestamps = false;

    // updated_at is managed by MySQL (ON UPDATE CURRENT_TIMESTAMP)
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'account_id',
        'tenant_id',
        'period_year',
        'period_month',
        'debit_total',
        'credit_total',
        'balance',
    ];

    protected $casts = [
        'debit_total'  => 'decimal:2',
        'credit_total' => 'decimal:2',
        'balance'      => 'decimal:2',
        'period_year'  => 'integer',
        'period_month' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForTenant(Builder $query, int $tenantId): void
    {
        $query->where('tenant_id', $tenantId);
    }

    public function scopeForPeriod(Builder $query, int $year, int $month): void
    {
        $query->where('period_year', $year)->where('period_month', $month);
    }

    public function scopeForYear(Builder $query, int $year): void
    {
        $query->where('period_year', $year);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Returns the net balance using the account's normal balance direction. */
    public function normalizedBalance(): float
    {
        if (! $this->relationLoaded('account')) {
            return (float) $this->balance;
        }

        return $this->account->isDebit()
            ? (float) $this->debit_total - (float) $this->credit_total
            : (float) $this->credit_total - (float) $this->debit_total;
    }
}

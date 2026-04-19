<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatement extends Model
{
    protected $fillable = [
        'company_id',
        'account_id',
        'statement_date',
        'opening_balance',
        'closing_balance',
        'status',
        'reconciled_at',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'statement_date'  => 'date',
        'reconciled_at'   => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class)->orderBy('transaction_date')->orderBy('id');
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

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isReconciled(): bool
    {
        return $this->status === 'reconciled';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'reconciled' => 'مُسوَّى',
            default      => 'مفتوح',
        };
    }

    public function statusMod(): string
    {
        return match ($this->status) {
            'reconciled' => 'posted',
            default      => 'draft',
        };
    }
}

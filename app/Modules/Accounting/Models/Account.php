<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'tenant_id',
        'parent_id',
        'code',
        'name',
        'type',
        'normal_balance',
        'is_system',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(AccountBalance::class, 'account_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeForTenant(Builder $query, int $tenantId): void
    {
        $query->where('tenant_id', $tenantId);
    }

    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isDebit(): bool
    {
        return $this->normal_balance === 'debit';
    }

    public function isCredit(): bool
    {
        return $this->normal_balance === 'credit';
    }
}

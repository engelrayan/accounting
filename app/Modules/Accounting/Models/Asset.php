<?php

namespace App\Modules\Accounting\Models;

use App\Modules\Accounting\Models\Concerns\TracksCreatedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    use TracksCreatedBy;

    protected $fillable = [
        'company_id',
        'name',
        'category',
        'purchase_date',
        'purchase_cost',
        'salvage_value',
        'useful_life',
        'account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'payment_account_id',
        'depreciated_months',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date'  => 'date',
        'purchase_cost'  => 'decimal:2',
        'salvage_value'  => 'decimal:2',
        'useful_life'    => 'integer',
        'depreciated_months' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    // -------------------------------------------------------------------------
    // Depreciation helpers
    // -------------------------------------------------------------------------

    /** Monthly straight-line depreciation amount. */
    public function monthlyDepreciation(): float
    {
        if ($this->useful_life === 0) return 0.0;

        return round(
            ((float) $this->purchase_cost - (float) $this->salvage_value) / $this->useful_life,
            2
        );
    }

    /** Total depreciation posted so far. */
    public function accumulatedDepreciation(): float
    {
        return round($this->monthlyDepreciation() * $this->depreciated_months, 2);
    }

    /** Current book value = cost − accumulated depreciation. */
    public function bookValue(): float
    {
        return max(0.0, (float) $this->purchase_cost - $this->accumulatedDepreciation());
    }

    /** Remaining depreciable amount before reaching salvage value. */
    public function remainingDepreciableAmount(): float
    {
        return max(0.0, (float) $this->purchase_cost - (float) $this->salvage_value - $this->accumulatedDepreciation());
    }

    /** Depreciation percentage complete. */
    public function depreciationProgress(): float
    {
        if ($this->useful_life === 0) return 100.0;

        return round(($this->depreciated_months / $this->useful_life) * 100, 1);
    }

    public function isFullyDepreciated(): bool
    {
        return $this->status === 'fully_depreciated'
            || $this->depreciated_months >= $this->useful_life;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function remainingMonths(): int
    {
        return max(0, $this->useful_life - $this->depreciated_months);
    }
}

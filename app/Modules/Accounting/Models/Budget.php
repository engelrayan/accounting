<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'fiscal_year',
        'status',
        'notes',
    ];

    // -------------------------------------------------------------------------
    // Relationships

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    // -------------------------------------------------------------------------
    // Scopes

    public function scopeForCompany(Builder $q, int $companyId): Builder
    {
        return $q->where('company_id', $companyId);
    }

    // -------------------------------------------------------------------------
    // Helpers

    public function isDraft(): bool   { return $this->status === 'draft'; }
    public function isActive(): bool  { return $this->status === 'active'; }
    public function isClosed(): bool  { return $this->status === 'closed'; }

    public function statusLabel(): string
    {
        return match($this->status) {
            'draft'  => 'مسودة',
            'active' => 'نشطة',
            'closed' => 'مغلقة',
            default  => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'draft'  => 'ac-badge--muted',
            'active' => 'ac-badge--success',
            'closed' => 'ac-badge--info',
            default  => 'ac-badge--muted',
        };
    }

    /** Total budget for the year across all lines */
    public function totalBudget(): float
    {
        return (float) $this->lines()->sum('amount');
    }
}

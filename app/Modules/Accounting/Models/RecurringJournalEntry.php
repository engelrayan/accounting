<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringJournalEntry extends Model
{
    protected $fillable = [
        'company_id',
        'description',
        'frequency',
        'start_date',
        'next_run_date',
        'last_run_date',
        'end_date',
        'is_active',
        'lines',
        'created_by',
    ];

    protected $casts = [
        'lines'         => 'array',
        'start_date'    => 'date',
        'next_run_date' => 'date',
        'last_run_date' => 'date',
        'end_date'      => 'date',
        'is_active'     => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // -------------------------------------------------------------------------
    // Scopes

    public function scopeForCompany(Builder $q, int $companyId): Builder
    {
        return $q->where('company_id', $companyId);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeDue(Builder $q): Builder
    {
        return $q->active()->where('next_run_date', '<=', now()->toDateString());
    }

    // -------------------------------------------------------------------------
    // Helpers

    public function frequencyLabel(): string
    {
        return match($this->frequency) {
            'daily'     => 'يومي',
            'weekly'    => 'أسبوعي',
            'monthly'   => 'شهري',
            'quarterly' => 'ربع سنوي',
            'yearly'    => 'سنوي',
            default     => $this->frequency,
        };
    }

    public function isDue(): bool
    {
        if (!$this->is_active) return false;
        if ($this->end_date && now()->gt($this->end_date)) return false;

        return $this->next_run_date->lte(now());
    }

    public function totalDebit(): float
    {
        return collect($this->lines)
            ->where('type', 'debit')
            ->sum(fn($l) => (float) ($l['amount'] ?? 0));
    }

    public function totalCredit(): float
    {
        return collect($this->lines)
            ->where('type', 'credit')
            ->sum(fn($l) => (float) ($l['amount'] ?? 0));
    }

    public function isBalanced(): bool
    {
        return abs($this->totalDebit() - $this->totalCredit()) < 0.01;
    }

    /** Compute the next scheduled date after today */
    public function computeNextRunDate(\Carbon\Carbon $after): \Carbon\Carbon
    {
        return match($this->frequency) {
            'daily'     => $after->copy()->addDay(),
            'weekly'    => $after->copy()->addWeek(),
            'monthly'   => $after->copy()->addMonth(),
            'quarterly' => $after->copy()->addMonths(3),
            'yearly'    => $after->copy()->addYear(),
            default     => $after->copy()->addMonth(),
        };
    }
}

<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'company_id',
        'period_year',
        'period_month',
        'status',
        'total_basic',
        'total_allowances',
        'total_deductions',
        'total_gross',
        'total_net',
        'journal_entry_id',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'period_year'      => 'integer',
        'period_month'     => 'integer',
        'total_basic'      => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_gross'      => 'decimal:2',
        'total_net'        => 'decimal:2',
        'approved_at'      => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollLine::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function periodLabel(): string
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير',  3 => 'مارس',     4 => 'أبريل',
            5 => 'مايو',  6 => 'يونيو',   7 => 'يوليو',    8 => 'أغسطس',
            9 => 'سبتمبر',10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return ($months[$this->period_month] ?? $this->period_month) . ' ' . $this->period_year;
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'draft'    => 'مسودة',
            'approved' => 'معتمد',
            'paid'     => 'مصروف',
            default    => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'draft'    => 'ac-badge--muted',
            'approved' => 'ac-badge--info',
            'paid'     => 'ac-badge--success',
            default    => '',
        };
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'paid']);
    }
}

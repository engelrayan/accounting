<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNote extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_id',
        'credit_note_number',
        'reason',
        'amount',
        'tax_amount',
        'total',
        'status',
        'issue_date',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total'      => 'decimal:2',
        'issue_date' => 'date',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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

    public function statusLabel(): string
    {
        return match ($this->status) {
            'issued' => 'صادر',
            default  => 'مسودة',
        };
    }

    public function statusMod(): string
    {
        return match ($this->status) {
            'issued' => 'posted',
            default  => 'draft',
        };
    }
}

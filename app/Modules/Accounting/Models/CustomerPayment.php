<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPayment extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_id',
        'payment_method',
        'amount',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
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
        return $this->belongsTo(CustomerInvoice::class, 'invoice_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    // -------------------------------------------------------------------------
    // Helpers — shared with Payment model
    // -------------------------------------------------------------------------

    public static function methodLabel(string $method): string
    {
        return match ($method) {
            'cash'       => 'نقداً',
            'bank'       => 'تحويل بنكي',
            'wallet'     => 'محفظة',
            'instapay'   => 'إنستاباي',
            'cheque'     => 'شيك',
            'card'       => 'بطاقة',
            'settlement' => 'تسوية حساب',
            default      => 'أخرى',
        };
    }

    public static function methodIcon(string $method): string
    {
        return match ($method) {
            'cash'       => '💵',
            'bank'       => '🏦',
            'wallet'     => '📱',
            'instapay'   => '⚡',
            'cheque'     => '📝',
            'card'       => '💳',
            'settlement' => '✅',
            default      => '💰',
        };
    }
}

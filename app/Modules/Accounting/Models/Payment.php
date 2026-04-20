<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_id',
        'amount',
        'payment_method',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
        'created_at'   => 'datetime',
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

    public static function methodLabel(string $method): string
    {
        return match ($method) {
            'cash'     => 'نقداً',
            'bank'     => 'تحويل بنكي',
            'wallet'   => 'محفظة إلكترونية',
            'instapay' => 'إنستاباي',
            'cheque'   => 'شيك',
            'card'     => 'بطاقة ائتمان',
            'settlement' => 'تسوية حساب',
            default    => 'أخرى',
        };
    }

    public static function methodIcon(string $method): string
    {
        return match ($method) {
            'cash'     => '💵',
            'bank'     => '🏦',
            'wallet'   => '📱',
            'instapay' => '⚡',
            'cheque'   => '📝',
            'card'     => '💳',
            'settlement' => '✓',
            default    => '💰',
        };
    }

    public function methodLabelAttr(): string
    {
        return self::methodLabel($this->payment_method);
    }
}

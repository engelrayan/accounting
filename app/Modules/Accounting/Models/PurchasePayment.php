<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'purchase_invoice_id',
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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
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
            'wallet'   => 'محفظة',
            'instapay' => 'إنستاباي',
            'cheque'   => 'شيك',
            'card'     => 'بطاقة',
            'settlement' => 'تسوية حساب',
            'other'    => 'أخرى',
            default    => $method,
        };
    }

    public static function methodIcon(string $method): string
    {
        return match ($method) {
            'cash'     => '💵',
            'bank'     => '🏦',
            'wallet'   => '👛',
            'instapay' => '📱',
            'cheque'   => '📄',
            'card'     => '💳',
            'settlement' => '✓',
            default    => '💰',
        };
    }

    public function methodLabelAttr(): string
    {
        return self::methodLabel($this->payment_method ?? '');
    }
}

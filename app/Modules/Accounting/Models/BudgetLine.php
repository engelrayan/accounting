<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetLine extends Model
{
    protected $fillable = [
        'budget_id',
        'account_id',
        'period_month',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    // -------------------------------------------------------------------------
    // Relationships

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // -------------------------------------------------------------------------
    // Helpers

    public static function monthName(int $month): string
    {
        $months = [
            1  => 'يناير',  2  => 'فبراير', 3  => 'مارس',
            4  => 'أبريل',  5  => 'مايو',   6  => 'يونيو',
            7  => 'يوليو',  8  => 'أغسطس',  9  => 'سبتمبر',
            10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        return $months[$month] ?? "شهر {$month}";
    }
}

<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'basic_salary',
        'allowances',
        'deductions',
        'gross_salary',
        'net_salary',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'net_salary'   => 'decimal:2',
        'allowances'   => 'array',
        'deductions'   => 'array',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function totalAllowances(): float
    {
        if (empty($this->allowances)) return 0.0;
        return collect($this->allowances)->sum(fn ($a) => (float) ($a['amount'] ?? 0));
    }

    public function totalDeductions(): float
    {
        if (empty($this->deductions)) return 0.0;
        return collect($this->deductions)->sum(fn ($d) => (float) ($d['amount'] ?? 0));
    }

    public function paymentMethodLabel(): string
    {
        return match($this->payment_method) {
            'cash'  => 'نقداً',
            'bank'  => 'بنكي',
            'other' => 'أخرى',
            default => $this->payment_method,
        };
    }
}

<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $fillable = [
        'company_id',
        'manager_id',
        'employee_number',
        'name',
        'national_id',
        'phone',
        'email',
        'department',
        'position',
        'hire_date',
        'basic_salary',
        'bank_account',
        'iban',
        'status',
        'password',
        'remember_token',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'hire_date'    => 'date',
        'basic_salary' => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /** المدير المباشر للموظف */
    public function manager(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /** الموظفون الذين يشرف عليهم هذا الموظف */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function payrollLines(): HasMany
    {
        return $this->hasMany(PayrollLine::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /** طلبات إجازة الفريق (الموظفين التابعين) */
    public function teamLeaveRequests(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            LeaveRequest::class,
            Employee::class,
            'manager_id',  // FK on employees
            'employee_id', // FK on leave_requests
            'id',          // local key on employees
            'id'           // local key on subordinates
        );
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
    // Helpers
    // -------------------------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function statusLabel(): string
    {
        return $this->status === 'active' ? 'نشط' : 'غير نشط';
    }

    /** هل هذا الموظف مدير (عنده موظفون تابعون)؟ */
    public function isManager(): bool
    {
        return $this->subordinates()->exists();
    }

    /** عدد طلبات الإجازة المعلّقة للفريق */
    public function pendingTeamLeavesCount(): int
    {
        return $this->teamLeaveRequests()->where('status', 'pending')->count();
    }

    /** كم يوم استخدم الموظف من نوع إجازة معين هذا العام */
    public function usedLeaveDays(int $leaveTypeId, int $year): int
    {
        return $this->leaveRequests()
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('days');
    }
}

<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'days_per_year',
        'requires_approval',
        'color',
        'is_active',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'is_active'         => 'boolean',
    ];

    // -------------------------------------------------------------------------

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    // -------------------------------------------------------------------------

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    // -------------------------------------------------------------------------

    public function isUnlimited(): bool
    {
        return $this->days_per_year === null;
    }
}

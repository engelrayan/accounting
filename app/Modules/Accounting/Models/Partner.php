<?php

namespace App\Modules\Accounting\Models;

use App\Modules\Accounting\Models\Concerns\TracksCreatedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Partner extends Model
{
    use TracksCreatedBy;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'name',
        'phone',
        'email',
        'notes',
        'capital_account_id',
        'drawing_account_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function capitalAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'capital_account_id');
    }

    public function drawingAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'drawing_account_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }
}

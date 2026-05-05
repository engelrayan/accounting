<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShipmentBatch extends Model
{
    protected $fillable = [
        'company_id',
        'shipment_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'shipment_date' => 'date',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(ShipmentEntry::class)->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }
}

<?php

namespace App\Modules\Accounting\Models\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Auto-fill created_by and updated_by from the authenticated user.
 * Add this trait to any Eloquent model that has these columns.
 */
trait TracksCreatedBy
{
    protected static function bootTracksCreatedBy(): void
    {
        static::creating(function ($model) {
            if (Auth::check() && empty($model->created_by)) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check() && in_array('updated_by', $model->getFillable(), true)) {
                $model->updated_by = Auth::id();
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}

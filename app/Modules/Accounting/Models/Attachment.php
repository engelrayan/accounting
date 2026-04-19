<?php

namespace App\Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'entity_type',
        'entity_id',
        'file_path',
        'file_name',
        'file_type',
        'uploaded_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
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

    /** True if the stored file physically exists on disk. */
    public function exists(): bool
    {
        return Storage::disk('public')->exists($this->file_path);
    }

    /** Human-readable file type label in Arabic. */
    public function typeLabel(): string
    {
        return match ($this->file_type) {
            'image' => 'صورة',
            'excel' => 'إكسيل',
            'pdf'   => 'PDF',
            default => 'ملف',
        };
    }

    /** CSS modifier for the badge colour. */
    public function typeMod(): string
    {
        return match ($this->file_type) {
            'image' => 'image',
            'excel' => 'excel',
            'pdf'   => 'pdf',
            default => 'file',
        };
    }

    /** Whether the browser can display it inline (image only). */
    public function isImage(): bool
    {
        return $this->file_type === 'image';
    }
}

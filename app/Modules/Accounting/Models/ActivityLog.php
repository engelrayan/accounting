<?php

namespace App\Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps   = false;
    public $incrementing = true;

    protected $table = 'activity_logs';

    protected $fillable = [
        'company_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'entity_label',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Arabic label for the action verb. */
    public function actionLabel(): string
    {
        return match ($this->action) {
            'created'      => 'أنشأ',
            'updated'      => 'عدَّل',
            'deleted'      => 'حذف',
            'posted'       => 'رحَّل',
            'reversed'     => 'عكس',
            'depreciated'  => 'سجَّل إهلاكاً',
            'deactivated'  => 'أوقف',
            'activated'    => 'فعَّل',
            'capital_add'  => 'أضاف رأس مال',
            'withdrawal'   => 'سجَّل سحباً',
            default        => $this->action,
        };
    }

    /** Arabic label for the entity type. */
    public function entityLabel(): string
    {
        return match ($this->entity_type) {
            'journal_entry' => 'قيد محاسبي',
            'transaction'   => 'معاملة',
            'asset'         => 'أصل ثابت',
            'partner'       => 'شريك',
            'account'       => 'حساب',
            'user'          => 'مستخدم',
            default         => $this->entity_type,
        };
    }
}

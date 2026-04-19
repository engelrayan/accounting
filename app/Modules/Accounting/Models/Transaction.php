<?php

namespace App\Modules\Accounting\Models;

use App\Modules\Accounting\Models\Concerns\TracksCreatedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use TracksCreatedBy;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'journal_entry_id',
        'type',
        'from_account_id',
        'to_account_id',
        'amount',
        'description',
        'transaction_date',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount'           => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'entity_id')
            ->where('entity_type', 'transaction')
            ->orderBy('id');
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

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'expense'          => 'مصروف',
            'income'           => 'إيراد',
            'transfer'         => 'تحويل',
            'capital_addition' => 'إضافة رأس مال',
            'withdrawal'       => 'سحب',
            default            => $type,
        };
    }
}

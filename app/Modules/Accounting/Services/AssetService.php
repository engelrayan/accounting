<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Exceptions\InvalidJournalEntryException;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\Asset;
use App\Modules\Accounting\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class AssetService
{
    public function __construct(
        private readonly JournalEntryService $journalService
    ) {}

    // -------------------------------------------------------------------------
    // Create asset + purchase journal entry
    // -------------------------------------------------------------------------

    /**
     * Create an asset record and automatically post the purchase journal entry.
     *
     * Purchase entry:
     *   DR  Asset Account         (purchase_cost)
     *   CR  Payment Account       (purchase_cost)
     *
     * $data keys: all Asset fillable fields except company_id and depreciated_months.
     *
     * @throws InvalidJournalEntryException
     */
    public function createAsset(array $data, int $companyId): Asset
    {
        // Resolve the three accounting accounts automatically from category
        $accounts = $this->resolveAccountsByCategory($data['category'], $companyId);

        return DB::transaction(function () use ($data, $companyId, $accounts) {
            $asset = Asset::create([
                ...$data,
                ...$accounts,
                'company_id'         => $companyId,
                'depreciated_months' => 0,
                'status'             => 'active',
            ]);

            // Purchase journal entry — delegated entirely to JournalEntryService
            $entry = $this->journalService->createEntry(
                [
                    'company_id'     => $companyId,
                    'description'    => "شراء أصل: {$asset->name}",
                    'entry_date'     => $asset->purchase_date->toDateString(),
                    'reference_type' => 'asset',
                    'reference_id'   => $asset->id,
                ],
                [
                    // DR Asset Account
                    ['account_id' => $asset->account_id,         'debit' => (float) $asset->purchase_cost, 'credit' => 0],
                    // CR Cash / Bank
                    ['account_id' => $asset->payment_account_id, 'debit' => 0, 'credit' => (float) $asset->purchase_cost],
                ]
            );

            $this->journalService->postEntry($entry);

            return $asset;
        });
    }

    // -------------------------------------------------------------------------
    // Monthly depreciation
    // -------------------------------------------------------------------------

    /**
     * Record one month of straight-line depreciation for an asset.
     *
     * Depreciation entry:
     *   DR  Depreciation Expense       (monthly amount)
     *   CR  Accumulated Depreciation   (monthly amount)
     *
     * @throws \DomainException
     * @throws InvalidJournalEntryException
     */
    public function recordDepreciation(Asset $asset, ?string $date = null): JournalEntry
    {
        if (! $asset->isActive()) {
            throw new \DomainException(
                "Asset [{$asset->name}] is not active (status: {$asset->status})."
            );
        }

        if ($asset->isFullyDepreciated()) {
            throw new \DomainException(
                "Asset [{$asset->name}] is already fully depreciated."
            );
        }

        // Use the smaller of monthly rate vs remaining depreciable amount
        // (handles the final period correctly)
        $amount = min(
            $asset->monthlyDepreciation(),
            $asset->remainingDepreciableAmount()
        );

        if ($amount <= 0) {
            throw new \DomainException(
                "No depreciable amount remaining for asset [{$asset->name}]."
            );
        }

        return DB::transaction(function () use ($asset, $amount, $date) {
            $entry = $this->journalService->createEntry(
                [
                    'company_id'     => $asset->company_id,
                    'description'    => "إهلاك — {$asset->name} (الشهر " . ($asset->depreciated_months + 1) . " من {$asset->useful_life})",
                    'entry_date'     => $date ?? today()->toDateString(),
                    'reference_type' => 'asset',
                    'reference_id'   => $asset->id,
                ],
                [
                    // DR Depreciation Expense
                    ['account_id' => $asset->depreciation_expense_account_id,     'debit' => $amount, 'credit' => 0],
                    // CR Accumulated Depreciation
                    ['account_id' => $asset->accumulated_depreciation_account_id, 'debit' => 0,       'credit' => $amount],
                ]
            );

            $this->journalService->postEntry($entry);

            // Advance the depreciation counter
            $asset->increment('depreciated_months');

            // Auto-close the asset when fully depreciated
            if ($asset->fresh()->isFullyDepreciated()) {
                $asset->update(['status' => 'fully_depreciated']);
            }

            return $entry;
        });
    }

    // -------------------------------------------------------------------------
    // Auto-resolve GL accounts from asset category
    // -------------------------------------------------------------------------

    /**
     * Map asset category → asset account code.
     * Accumulated depreciation (1290) and depreciation expense (5500)
     * are always the same regardless of category.
     *
     * @throws \DomainException if a required account is not found for this company
     */
    private function resolveAccountsByCategory(string $category, int $companyId): array
    {
        // Asset account by category
        $assetCodeMap = [
            'vehicle'   => '1220',   // السيارات
            'equipment' => '1210',   // المعدات
            'furniture' => '1210',   // المعدات (fallback)
            'building'  => '1200',   // الأصول الثابتة (fallback)
            'other'     => '1210',   // المعدات (fallback)
        ];

        $assetCode = $assetCodeMap[$category] ?? '1210';

        $find = function (string $code) use ($companyId): Account {
            $account = Account::where('tenant_id', $companyId)
                ->where('code', $code)
                ->first();

            if (! $account) {
                throw new \DomainException(
                    "الحساب برقم {$code} غير موجود. يرجى التأكد من إعداد دليل الحسابات أولاً."
                );
            }

            return $account;
        };

        return [
            'account_id'                          => $find($assetCode)->id,
            'accumulated_depreciation_account_id' => $find('1290')->id,
            'depreciation_expense_account_id'     => $find('5500')->id,
        ];
    }
}

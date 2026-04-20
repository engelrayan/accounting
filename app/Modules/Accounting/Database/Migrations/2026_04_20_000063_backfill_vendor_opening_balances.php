<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->allowSettlementPurchasePayments();
        $this->backfillVendorOpeningBalances();
    }

    public function down(): void
    {
        // Data safety: never delete posted financial entries during rollback.
    }

    private function allowSettlementPurchasePayments(): void
    {
        if (! Schema::hasTable('purchase_payments') || ! Schema::hasColumn('purchase_payments', 'payment_method')) {
            return;
        }

        DB::statement(
            "ALTER TABLE `purchase_payments` MODIFY `payment_method` ENUM('cash','bank','wallet','instapay','cheque','card','other','settlement') NOT NULL DEFAULT 'cash'"
        );
    }

    private function backfillVendorOpeningBalances(): void
    {
        if (
            ! Schema::hasTable('vendors') ||
            ! Schema::hasTable('accounts') ||
            ! Schema::hasTable('journal_entries') ||
            ! Schema::hasTable('journal_lines')
        ) {
            return;
        }

        DB::table('vendors')
            ->where('opening_balance', '>', 0)
            ->orderBy('id')
            ->chunkById(100, function ($vendors): void {
                foreach ($vendors as $vendor) {
                    $this->postVendorOpeningBalance($vendor);
                }
            });
    }

    private function postVendorOpeningBalance(object $vendor): void
    {
        $companyId = (int) $vendor->company_id;
        $amount = round((float) $vendor->opening_balance, 2);

        if ($companyId <= 0 || $amount <= 0) {
            return;
        }

        DB::transaction(function () use ($vendor, $companyId, $amount): void {
            $alreadyPosted = DB::table('journal_entries')
                ->where('tenant_id', $companyId)
                ->where('reference_type', 'vendor_opening_balance')
                ->where('reference_id', $vendor->id)
                ->where('status', '<>', 'reversed')
                ->exists();

            if ($alreadyPosted) {
                return;
            }

            $accounts = $this->ensureRequiredAccounts($companyId);
            $now = now();
            $entryDate = $vendor->created_at
                ? substr((string) $vendor->created_at, 0, 10)
                : $now->toDateString();

            $entry = [
                'tenant_id' => $companyId,
                'entry_number' => $this->nextEntryNumber($companyId),
                'reference_type' => 'vendor_opening_balance',
                'reference_id' => $vendor->id,
                'description' => "رصيد افتتاحي مستحق للمورد [{$vendor->name}]",
                'entry_date' => $entryDate,
                'status' => 'posted',
                'created_by' => $this->fallbackUserId($companyId),
                'posted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('journal_entries', 'auto_generated')) {
                $entry['auto_generated'] = true;
            }

            $entryId = DB::table('journal_entries')->insertGetId($entry);

            DB::table('journal_lines')->insert([
                [
                    'journal_entry_id' => $entryId,
                    'account_id' => $accounts['opening_equity'],
                    'description' => 'تحميل الرصيد الافتتاحي على حقوق الملكية الافتتاحية',
                    'debit' => $amount,
                    'credit' => 0,
                    'currency' => 'SAR',
                    'exchange_rate' => 1,
                ],
                [
                    'journal_entry_id' => $entryId,
                    'account_id' => $accounts['accounts_payable'],
                    'description' => "التزام افتتاحي للمورد {$vendor->name}",
                    'debit' => 0,
                    'credit' => $amount,
                    'currency' => 'SAR',
                    'exchange_rate' => 1,
                ],
            ]);
        });
    }

    private function ensureRequiredAccounts(int $companyId): array
    {
        $ids = [];
        $now = now();

        foreach ($this->requiredAccounts() as $account) {
            $existingId = DB::table('accounts')
                ->where('tenant_id', $companyId)
                ->where('code', $account['code'])
                ->value('id');

            if ($existingId) {
                $ids[$account['code']] = (int) $existingId;
                continue;
            }

            $parentId = null;
            if ($account['parent']) {
                $parentId = $ids[$account['parent']]
                    ?? DB::table('accounts')
                        ->where('tenant_id', $companyId)
                        ->where('code', $account['parent'])
                        ->value('id');
            }

            $ids[$account['code']] = DB::table('accounts')->insertGetId([
                'tenant_id' => $companyId,
                'parent_id' => $parentId,
                'code' => $account['code'],
                'name' => $account['name'],
                'type' => $account['type'],
                'normal_balance' => $account['normal_balance'],
                'is_system' => true,
                'is_active' => true,
                'description' => 'تم إنشاؤه تلقائياً لدعم أرصدة الموردين الافتتاحية.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return [
            'accounts_payable' => $ids['2110'],
            'opening_equity' => $ids['3300'],
        ];
    }

    private function requiredAccounts(): array
    {
        return [
            ['code' => '2000', 'name' => 'الالتزامات', 'type' => 'liability', 'normal_balance' => 'credit', 'parent' => null],
            ['code' => '2100', 'name' => 'الالتزامات المتداولة', 'type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2000'],
            ['code' => '2110', 'name' => 'ذمم دائنة (موردون)', 'type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2100'],
            ['code' => '3000', 'name' => 'حقوق الملكية', 'type' => 'equity', 'normal_balance' => 'credit', 'parent' => null],
            ['code' => '3300', 'name' => 'الأرباح المحتجزة / أرصدة افتتاحية', 'type' => 'equity', 'normal_balance' => 'credit', 'parent' => '3000'],
        ];
    }

    private function nextEntryNumber(int $companyId): string
    {
        $year = now()->year;
        $sequence = DB::table('journal_entries')
            ->where('tenant_id', $companyId)
            ->where('entry_number', 'like', "JE-{$year}-%")
            ->count() + 1;

        do {
            $entryNumber = sprintf('JE-%d-%05d', $year, $sequence++);
        } while (
            DB::table('journal_entries')
                ->where('tenant_id', $companyId)
                ->where('entry_number', $entryNumber)
                ->exists()
        );

        return $entryNumber;
    }

    private function fallbackUserId(int $companyId): int
    {
        if (! Schema::hasTable('users')) {
            return 1;
        }

        if (Schema::hasColumn('users', 'company_id')) {
            $companyUserId = DB::table('users')
                ->where('company_id', $companyId)
                ->value('id');

            if ($companyUserId) {
                return (int) $companyUserId;
            }
        }

        return (int) (DB::table('users')->value('id') ?? 1);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            return;
        }

        foreach ($this->companyIds() as $companyId) {
            $this->ensureAccounts((int) $companyId);
        }
    }

    public function down(): void
    {
        // Data safety: never remove chart-of-account rows during rollback.
    }

    private function companyIds(): array
    {
        $ids = collect();

        foreach ([
            ['users', 'company_id'],
            ['vendors', 'company_id'],
            ['purchase_invoices', 'company_id'],
            ['purchase_payments', 'company_id'],
            ['accounts', 'tenant_id'],
        ] as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                $ids = $ids->merge(
                    DB::table($table)
                        ->whereNotNull($column)
                        ->distinct()
                        ->pluck($column)
                );
            }
        }

        $ids = $ids->map(fn ($id) => (int) $id)->filter()->unique()->values();

        return $ids->isEmpty() ? [1] : $ids->all();
    }

    private function ensureAccounts(int $companyId): void
    {
        $created = [];
        $now = now();

        foreach ($this->accounts() as $account) {
            $existingId = DB::table('accounts')
                ->where('tenant_id', $companyId)
                ->where('code', $account['code'])
                ->value('id');

            if ($existingId) {
                $created[$account['code']] = (int) $existingId;
                continue;
            }

            $parentId = null;
            if ($account['parent']) {
                $parentId = $created[$account['parent']]
                    ?? DB::table('accounts')
                        ->where('tenant_id', $companyId)
                        ->where('code', $account['parent'])
                        ->value('id');
            }

            $created[$account['code']] = DB::table('accounts')->insertGetId([
                'tenant_id' => $companyId,
                'parent_id' => $parentId,
                'code' => $account['code'],
                'name' => $account['name'],
                'type' => $account['type'],
                'normal_balance' => $account['normal_balance'],
                'is_system' => true,
                'is_active' => true,
                'description' => 'تم إنشاؤه تلقائياً لدعم فواتير الشراء ومدفوعات الموردين.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function accounts(): array
    {
        return [
            ['code' => '1000', 'name' => 'الأصول', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => null],
            ['code' => '1100', 'name' => 'الأصول المتداولة', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1000'],
            ['code' => '1110', 'name' => 'الخزنة', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1100'],
            ['code' => '1120', 'name' => 'البنك', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1100'],
            ['code' => '1300', 'name' => 'المخزون', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1000'],
            ['code' => '2000', 'name' => 'الالتزامات', 'type' => 'liability', 'normal_balance' => 'credit', 'parent' => null],
            ['code' => '2100', 'name' => 'الالتزامات المتداولة', 'type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2000'],
            ['code' => '2110', 'name' => 'ذمم دائنة (موردون)', 'type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2100'],
            ['code' => '5000', 'name' => 'المصروفات', 'type' => 'expense', 'normal_balance' => 'debit', 'parent' => null],
            ['code' => '5100', 'name' => 'المشتريات', 'type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],
        ];
    }
};

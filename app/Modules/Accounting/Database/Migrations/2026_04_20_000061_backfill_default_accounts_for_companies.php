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

        $companyIds = collect();

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'company_id')) {
            $companyIds = $companyIds->merge(
                DB::table('users')
                    ->whereNotNull('company_id')
                    ->distinct()
                    ->pluck('company_id')
            );
        }

        $companyIds = $companyIds->merge(
            DB::table('accounts')
                ->whereNotNull('tenant_id')
                ->distinct()
                ->pluck('tenant_id')
        )
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($companyIds->isEmpty()) {
            $companyIds = collect([1]);
        }

        foreach ($companyIds as $companyId) {
            $this->backfillCompanyAccounts($companyId);
        }
    }

    public function down(): void
    {
        // Data safety: never delete financial accounts during rollback.
    }

    private function backfillCompanyAccounts(int $companyId): void
    {
        $now = now();
        $created = [];

        foreach ($this->defaultAccounts() as $account) {
            $existing = DB::table('accounts')
                ->where('tenant_id', $companyId)
                ->where('code', $account['code'])
                ->first(['id']);

            if ($existing) {
                $created[$account['code']] = $existing->id;
                continue;
            }

            $parentId = null;
            if ($account['parent'] !== null) {
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
                'description' => 'تم إنشاؤه تلقائياً لإكمال دليل الحسابات الأساسي.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function defaultAccounts(): array
    {
        return [
            ['code' => '1000', 'name' => 'الأصول', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => null],
            ['code' => '1100', 'name' => 'الأصول المتداولة', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1000'],
            ['code' => '1110', 'name' => 'الخزنة', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1100'],
            ['code' => '1120', 'name' => 'البنك', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1100'],
            ['code' => '1130', 'name' => 'ذمم مدينة (عملاء)', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1100'],
            ['code' => '1200', 'name' => 'الأصول الثابتة', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1000'],
            ['code' => '1210', 'name' => 'المعدات', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1200'],
            ['code' => '1220', 'name' => 'السيارات', 'type' => 'asset', 'normal_balance' => 'debit', 'parent' => '1200'],
            ['code' => '1290', 'name' => 'مجمع الإهلاك', 'type' => 'asset', 'normal_balance' => 'credit', 'parent' => '1200'],

            ['code' => '2000', 'name' => 'الالتزامات', 'type' => 'liability', 'normal_balance' => 'credit', 'parent' => null],
            ['code' => '2100', 'name' => 'الالتزامات المتداولة', 'type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2000'],
            ['code' => '2110', 'name' => 'ذمم دائنة (موردون)', 'type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2100'],
            ['code' => '2120', 'name' => 'رواتب مستحقة', 'type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2100'],
            ['code' => '2130', 'name' => 'ضرائب مستحقة', 'type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2100'],

            ['code' => '3000', 'name' => 'حقوق الملكية', 'type' => 'equity', 'normal_balance' => 'credit', 'parent' => null],
            ['code' => '3100', 'name' => 'رأس المال', 'type' => 'equity', 'normal_balance' => 'credit', 'parent' => '3000'],
            ['code' => '3200', 'name' => 'المسحوبات', 'type' => 'equity', 'normal_balance' => 'debit', 'parent' => '3000'],
            ['code' => '3300', 'name' => 'الأرباح المحتجزة', 'type' => 'equity', 'normal_balance' => 'credit', 'parent' => '3000'],

            ['code' => '4000', 'name' => 'الإيرادات', 'type' => 'revenue', 'normal_balance' => 'credit', 'parent' => null],
            ['code' => '4100', 'name' => 'إيرادات المبيعات', 'type' => 'revenue', 'normal_balance' => 'credit', 'parent' => '4000'],
            ['code' => '4200', 'name' => 'إيرادات خدمات', 'type' => 'revenue', 'normal_balance' => 'credit', 'parent' => '4000'],
            ['code' => '4300', 'name' => 'إيرادات أخرى', 'type' => 'revenue', 'normal_balance' => 'credit', 'parent' => '4000'],

            ['code' => '5000', 'name' => 'المصروفات', 'type' => 'expense', 'normal_balance' => 'debit', 'parent' => null],
            ['code' => '5100', 'name' => 'مصروف الرواتب', 'type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],
            ['code' => '5200', 'name' => 'مصروف الإيجار', 'type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],
            ['code' => '5300', 'name' => 'وقود ومواصلات', 'type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],
            ['code' => '5400', 'name' => 'مصروف المرافق', 'type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],
            ['code' => '5500', 'name' => 'مصروف الإهلاك', 'type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],
            ['code' => '5600', 'name' => 'مصروفات أخرى', 'type' => 'expense', 'normal_balance' => 'debit', 'parent' => '5000'],
        ];
    }
};

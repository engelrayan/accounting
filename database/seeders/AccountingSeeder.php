<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Accounting\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccountingSeeder extends Seeder
{
    private int $companyId = 1;

    public function run(): void
    {
        $this->createTestUser();
        $this->createChartOfAccounts();
    }

    // -------------------------------------------------------------------------

    private function createTestUser(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sinbad.test'],
            [
                'name'       => 'Admin',
                'company_id' => $this->companyId,
                'password'   => Hash::make('password'),
            ]
        );
    }

    private function createChartOfAccounts(): void
    {
        $accounts = [
            // ── الأصول ───────────────────────────────────────────────────
            ['code' => '1000', 'name' => 'الأصول',                'type' => 'asset',     'normal_balance' => 'debit',  'parent' => null],
            ['code' => '1100', 'name' => 'الأصول المتداولة',      'type' => 'asset',     'normal_balance' => 'debit',  'parent' => '1000'],
            ['code' => '1110', 'name' => 'الخزنة',                'type' => 'asset',     'normal_balance' => 'debit',  'parent' => '1100'],
            ['code' => '1120', 'name' => 'البنك',                 'type' => 'asset',     'normal_balance' => 'debit',  'parent' => '1100'],
            ['code' => '1130', 'name' => 'ذمم مدينة (عملاء)',     'type' => 'asset',     'normal_balance' => 'debit',  'parent' => '1100'],
            ['code' => '1200', 'name' => 'الأصول الثابتة',        'type' => 'asset',     'normal_balance' => 'debit',  'parent' => '1000'],
            ['code' => '1210', 'name' => 'المعدات',               'type' => 'asset',     'normal_balance' => 'debit',  'parent' => '1200'],
            ['code' => '1220', 'name' => 'السيارات',              'type' => 'asset',     'normal_balance' => 'debit',  'parent' => '1200'],
            ['code' => '1290', 'name' => 'مجمع الإهلاك',          'type' => 'asset',     'normal_balance' => 'credit', 'parent' => '1200'],

            // ── الالتزامات ───────────────────────────────────────────────
            ['code' => '2000', 'name' => 'الالتزامات',            'type' => 'liability', 'normal_balance' => 'credit', 'parent' => null],
            ['code' => '2100', 'name' => 'الالتزامات المتداولة',  'type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2000'],
            ['code' => '2110', 'name' => 'ذمم دائنة (موردون)',    'type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2100'],
            ['code' => '2120', 'name' => 'رواتب مستحقة',          'type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2100'],
            ['code' => '2130', 'name' => 'ضرائب مستحقة',          'type' => 'liability', 'normal_balance' => 'credit', 'parent' => '2100'],

            // ── حقوق الملكية ─────────────────────────────────────────────
            ['code' => '3000', 'name' => 'حقوق الملكية',          'type' => 'equity',    'normal_balance' => 'credit', 'parent' => null],
            ['code' => '3100', 'name' => 'رأس المال',             'type' => 'equity',    'normal_balance' => 'credit', 'parent' => '3000'],
            ['code' => '3200', 'name' => 'المسحوبات',             'type' => 'equity',    'normal_balance' => 'debit',  'parent' => '3000'],
            ['code' => '3300', 'name' => 'الأرباح المحتجزة',      'type' => 'equity',    'normal_balance' => 'credit', 'parent' => '3000'],

            // ── الإيرادات ────────────────────────────────────────────────
            ['code' => '4000', 'name' => 'الإيرادات',             'type' => 'revenue',   'normal_balance' => 'credit', 'parent' => null],
            ['code' => '4100', 'name' => 'إيرادات شحن',           'type' => 'revenue',   'normal_balance' => 'credit', 'parent' => '4000'],
            ['code' => '4200', 'name' => 'إيرادات خدمات',         'type' => 'revenue',   'normal_balance' => 'credit', 'parent' => '4000'],
            ['code' => '4300', 'name' => 'إيرادات أخرى',          'type' => 'revenue',   'normal_balance' => 'credit', 'parent' => '4000'],

            // ── المصروفات ────────────────────────────────────────────────
            ['code' => '5000', 'name' => 'المصروفات',             'type' => 'expense',   'normal_balance' => 'debit',  'parent' => null],
            ['code' => '5100', 'name' => 'مصروف الرواتب',         'type' => 'expense',   'normal_balance' => 'debit',  'parent' => '5000'],
            ['code' => '5200', 'name' => 'مصروف الإيجار',         'type' => 'expense',   'normal_balance' => 'debit',  'parent' => '5000'],
            ['code' => '5300', 'name' => 'وقود ومواصلات',         'type' => 'expense',   'normal_balance' => 'debit',  'parent' => '5000'],
            ['code' => '5400', 'name' => 'مصروف المرافق',         'type' => 'expense',   'normal_balance' => 'debit',  'parent' => '5000'],
            ['code' => '5500', 'name' => 'مصروف الإهلاك',         'type' => 'expense',   'normal_balance' => 'debit',  'parent' => '5000'],
            ['code' => '5600', 'name' => 'مصروفات أخرى',          'type' => 'expense',   'normal_balance' => 'debit',  'parent' => '5000'],
        ];

        // First pass — create root accounts (parent = null)
        $created = [];
        foreach ($accounts as $row) {
            if ($row['parent'] !== null) continue;

            $created[$row['code']] = Account::updateOrCreate(
                ['tenant_id' => $this->companyId, 'code' => $row['code']],
                ['name' => $row['name'], 'type' => $row['type'], 'normal_balance' => $row['normal_balance'], 'is_system' => true]
            );
        }

        // Second pass — children (one level resolves parents from first pass)
        foreach ($accounts as $row) {
            if ($row['parent'] === null) continue;

            $parentCode = substr($row['code'], 0, strlen($row['code']) - (strlen($row['code']) - strlen($row['parent'])));

            // Find the closest parent by code prefix
            $parent = $created[$row['parent']] ?? null;

            if (! $parent) {
                // parent might have been created in this pass
                $parent = Account::where('tenant_id', $this->companyId)
                    ->where('code', $row['parent'])
                    ->first();
            }

            $created[$row['code']] = Account::updateOrCreate(
                ['tenant_id' => $this->companyId, 'code' => $row['code']],
                [
                    'name'           => $row['name'],
                    'type'           => $row['type'],
                    'normal_balance' => $row['normal_balance'],
                    'parent_id'      => $parent?->id,
                    'is_system'      => true,
                ]
            );
        }
    }
}

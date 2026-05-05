<?php

return [
    'dashboard' => [
        'key' => 'dashboard',
        'default_label' => 'لوحة التحكم',
        'section' => null,
        'icon' => 'dashboard',
        'route' => 'accounting.dashboard',
        'active_routes' => ['accounting.dashboard'],
    ],

    'transactions' => [
        'key' => 'transactions',
        'default_label' => 'المعاملات',
        'section' => 'المالية',
        'icon' => 'transactions',
        'route' => 'accounting.transactions.index',
        'active_routes' => ['accounting.transactions.*'],
    ],

    'accounts' => [
        'key' => 'accounts',
        'default_label' => 'الحسابات',
        'section' => 'المالية',
        'icon' => 'accounts',
        'route' => 'accounting.accounts.index',
        'active_routes' => ['accounting.accounts.*'],
    ],

    'partners' => [
        'key' => 'partners',
        'default_label' => 'الشركاء',
        'section' => 'المالية',
        'icon' => 'partners',
        'route' => 'accounting.partners.index',
        'active_routes' => ['accounting.partners.*'],
    ],

    'customers' => [
        'key' => 'customers',
        'default_label' => 'العملاء',
        'section' => 'المالية',
        'icon' => 'customers',
        'route' => 'accounting.customers.index',
        'active_routes' => ['accounting.customers.*'],
    ],

    'quotations' => [
        'key' => 'quotations',
        'default_label' => 'عروض الأسعار',
        'section' => 'المالية',
        'icon' => 'quotations',
        'route' => 'accounting.quotations.index',
        'active_routes' => ['accounting.quotations.*'],
    ],

    'invoices' => [
        'key' => 'invoices',
        'default_label' => 'الفواتير',
        'section' => 'المالية',
        'icon' => 'invoices',
        'route' => 'accounting.invoices.index',
        'active_routes' => [
            'accounting.invoices.*',
            'accounting.credit-notes.*',
        ],
    ],

    'pos' => [
        'key' => 'pos',
        'default_label' => 'نقطة البيع',
        'section' => 'المالية',
        'icon' => 'pos',
        'route' => 'accounting.pos.create',
        'active_routes' => ['accounting.pos.*'],
    ],

    'payments' => [
        'key' => 'payments',
        'default_label' => 'المدفوعات',
        'section' => 'المالية',
        'icon' => 'payments',
        'route' => 'accounting.payments.index',
        'active_routes' => ['accounting.payments.*'],
    ],

    'journal_entries' => [
        'key' => 'journal_entries',
        'default_label' => 'الحركات المحاسبية',
        'section' => 'المالية',
        'icon' => 'journal',
        'route' => 'accounting.journal-entries.index',
        'active_routes' => ['accounting.journal-entries.*'],
    ],

    'vendors' => [
        'key' => 'vendors',
        'default_label' => 'الموردون',
        'section' => 'الموردون',
        'icon' => 'vendors',
        'route' => 'accounting.vendors.index',
        'active_routes' => ['accounting.vendors.*'],
    ],

    'purchase_orders' => [
        'key' => 'purchase_orders',
        'default_label' => 'أوامر الشراء',
        'section' => 'الموردون',
        'icon' => 'purchase_orders',
        'route' => 'accounting.purchase-orders.index',
        'active_routes' => ['accounting.purchase-orders.*'],
    ],

    'purchase_invoices' => [
        'key' => 'purchase_invoices',
        'default_label' => 'فواتير الشراء',
        'section' => 'الموردون',
        'icon' => 'purchase_invoices',
        'route' => 'accounting.purchase-invoices.index',
        'active_routes' => ['accounting.purchase-invoices.*'],
    ],

    'purchase_payments' => [
        'key' => 'purchase_payments',
        'default_label' => 'مدفوعات الموردين',
        'section' => 'الموردون',
        'icon' => 'purchase_payments',
        'route' => 'accounting.purchase-payments.index',
        'active_routes' => ['accounting.purchase-payments.*'],
    ],

    'budget' => [
        'key' => 'budget',
        'default_label' => 'الميزانية التقديرية',
        'section' => 'التخطيط المالي',
        'icon' => 'budget',
        'route' => 'accounting.budget.index',
        'active_routes' => ['accounting.budget.*'],
    ],

    'recurring' => [
        'key' => 'recurring',
        'default_label' => 'القيود المتكررة',
        'section' => 'التخطيط المالي',
        'icon' => 'recurring',
        'route' => 'accounting.recurring.index',
        'active_routes' => ['accounting.recurring.*'],
    ],

    'employees' => [
        'key' => 'employees',
        'default_label' => 'الموظفون',
        'section' => 'الموارد البشرية',
        'icon' => 'employees',
        'route' => 'accounting.employees.index',
        'active_routes' => ['accounting.employees.*'],
    ],

    'payroll' => [
        'key' => 'payroll',
        'default_label' => 'مسير الرواتب',
        'section' => 'الموارد البشرية',
        'icon' => 'payroll',
        'route' => 'accounting.payroll.index',
        'active_routes' => ['accounting.payroll.*'],
    ],

    'leaves' => [
        'key' => 'leaves',
        'default_label' => 'طلبات الإجازة',
        'section' => 'الموارد البشرية',
        'icon' => 'leaves',
        'route' => 'accounting.leaves.index',
        'active_routes' => ['accounting.leaves.*'],
    ],

    'products' => [
        'key' => 'products',
        'default_label' => 'المنتجات والخدمات',
        'section' => 'الكتالوج والمخزون',
        'icon' => 'products',
        'route' => 'accounting.products.index',
        'active_routes' => ['accounting.products.*'],
    ],

    'inventory' => [
        'key' => 'inventory',
        'default_label' => 'المخزون',
        'section' => 'الكتالوج والمخزون',
        'icon' => 'inventory',
        'route' => 'accounting.inventory.index',
        'active_routes' => ['accounting.inventory.*'],
    ],

    'bank_reconciliation' => [
        'key' => 'bank_reconciliation',
        'default_label' => 'التسوية البنكية',
        'section' => 'البنك',
        'icon' => 'bank',
        'route' => 'accounting.bank-reconciliation.index',
        'active_routes' => ['accounting.bank-reconciliation.*'],
    ],

    'assets' => [
        'key' => 'assets',
        'default_label' => 'الأصول الثابتة',
        'section' => 'الأصول',
        'icon' => 'assets',
        'route' => 'accounting.assets.index',
        'active_routes' => ['accounting.assets.*'],
    ],

    'reports' => [
        'key' => 'reports',
        'default_label' => 'التقارير',
        'section' => 'التقارير',
        'icon' => 'reports',
        'route' => 'accounting.reports.income-expense',
        'active_routes' => ['accounting.reports.*'],
        'show_in_sidebar' => false,
    ],

    'report_income_expense' => [
        'key' => 'report_income_expense',
        'default_label' => 'الدخل والمصروف',
        'section' => 'التقارير',
        'icon' => 'reports',
        'route' => 'accounting.reports.income-expense',
        'active_routes' => ['accounting.reports.income-expense'],
    ],

    'report_trial_balance' => [
        'key' => 'report_trial_balance',
        'default_label' => 'ميزان المراجعة',
        'section' => 'التقارير',
        'icon' => 'trial_balance',
        'route' => 'accounting.reports.trial-balance',
        'active_routes' => ['accounting.reports.trial-balance'],
    ],

    'report_profit_loss' => [
        'key' => 'report_profit_loss',
        'default_label' => 'الأرباح والخسائر',
        'section' => 'التقارير',
        'icon' => 'profit_loss',
        'route' => 'accounting.reports.profit-loss',
        'active_routes' => ['accounting.reports.profit-loss'],
    ],

    'report_cashier_sales' => [
        'key' => 'report_cashier_sales',
        'default_label' => 'مبيعات الكاشير',
        'section' => 'التقارير',
        'icon' => 'cashier',
        'route' => 'accounting.reports.cashier-sales',
        'active_routes' => ['accounting.reports.cashier-sales'],
    ],

    'report_balance_sheet' => [
        'key' => 'report_balance_sheet',
        'default_label' => 'الميزانية العمومية',
        'section' => 'التقارير',
        'icon' => 'assets',
        'route' => 'accounting.reports.balance-sheet',
        'active_routes' => ['accounting.reports.balance-sheet'],
    ],

    'report_ar_aging' => [
        'key' => 'report_ar_aging',
        'default_label' => 'تقادم الذمم المدينة',
        'section' => 'التقارير',
        'icon' => 'clock',
        'route' => 'accounting.reports.ar-aging',
        'active_routes' => ['accounting.reports.ar-aging'],
    ],

    'report_ap_aging' => [
        'key' => 'report_ap_aging',
        'default_label' => 'تقادم الذمم الدائنة',
        'section' => 'التقارير',
        'icon' => 'clock_reverse',
        'route' => 'accounting.reports.ap-aging',
        'active_routes' => ['accounting.reports.ap-aging'],
    ],

    'report_vat' => [
        'key' => 'report_vat',
        'default_label' => 'تقرير ضريبة القيمة المضافة',
        'section' => 'التقارير',
        'icon' => 'vat',
        'route' => 'accounting.reports.vat-report',
        'active_routes' => ['accounting.reports.vat-report'],
    ],

    'price_lists' => [
        'key' => 'price_lists',
        'default_label' => 'قوائم الأسعار',
        'section' => 'التسعير',
        'icon' => 'price_list',
        'route' => 'accounting.price-lists.index',
        'active_routes' => ['accounting.price-lists.*'],
    ],

    'governorates' => [
        'key' => 'governorates',
        'default_label' => 'المحافظات',
        'section' => 'التسعير',
        'icon' => 'governorates',
        'route' => 'accounting.governorates.index',
        'active_routes' => ['accounting.governorates.*'],
    ],
    'customer_shipments' => [
        'key' => 'customer_shipments',
        'default_label' => 'شحنات العملاء',
        'section' => 'التسعير',
        'icon' => 'customer_shipments',
        'route' => 'accounting.customer-shipments.index',
        'active_routes' => ['accounting.customer-shipments.*'],
    ],
];

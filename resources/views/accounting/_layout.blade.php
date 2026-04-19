<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'الرئيسية') — محاسب عام</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/accounting.css') }}">
</head>
<body class="ac-app">

{{-- ══════════════════════════════════════════════════════════ SIDEBAR --}}
<aside class="ac-sidebar">

    {{-- Brand --}}
    <div class="ac-sidebar__brand">
        <svg class="ac-sidebar__brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
            <path d="M2 17l10 5 10-5"/>
            <path d="M2 12l10 5 10-5"/>
        </svg>
        <span>محاسب عام</span>
    </div>

    <nav class="ac-sidebar__nav">

        {{-- لوحة التحكم --}}
        <div class="ac-sidebar__section">
            <a href="{{ route('accounting.dashboard') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.dashboard') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                لوحة التحكم
            </a>
        </div>

        {{-- المالية --}}
        <div class="ac-sidebar__section">
            <span class="ac-sidebar__section-title">المالية</span>

            <a href="{{ route('accounting.transactions.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.transactions.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                </svg>
                المعاملات
            </a>

            <a href="{{ route('accounting.accounts.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.accounts.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                الحسابات
            </a>

            <a href="{{ route('accounting.partners.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.partners.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                    <path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
                الشركاء
            </a>

            <a href="{{ route('accounting.customers.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.customers.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                العملاء
            </a>

            <a href="{{ route('accounting.quotations.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.quotations.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <circle cx="10" cy="9" r="1" fill="currentColor"/>
                </svg>
                عروض الأسعار
            </a>

            <a href="{{ route('accounting.invoices.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.invoices.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <line x1="10" y1="9" x2="8" y2="9"/>
                </svg>
                الفواتير
            </a>

            <a href="{{ route('accounting.pos.create') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.pos.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16"/>
                    <path d="M6 6v11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6"/>
                    <path d="M9 10h6"/>
                    <path d="M9 14h6"/>
                    <path d="M10 18h4"/>
                </svg>
                نقطة البيع
            </a>

            <a href="{{ route('accounting.payments.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.payments.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                    <line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
                المدفوعات
            </a>

            <a href="{{ route('accounting.journal-entries.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.journal-entries.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                    <line x1="8" y1="7" x2="16" y2="7"/>
                    <line x1="8" y1="11" x2="13" y2="11"/>
                </svg>
                الحركات المحاسبية
            </a>
        </div>

        {{-- الموردون (AP) --}}
        <div class="ac-sidebar__section">
            <span class="ac-sidebar__section-title">الموردون</span>

            <a href="{{ route('accounting.vendors.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.vendors.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    <polyline points="9,22 9,12 15,12 15,22"/>
                </svg>
                الموردون
            </a>

            <a href="{{ route('accounting.purchase-orders.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.purchase-orders.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 01-8 0"/>
                </svg>
                أوامر الشراء
            </a>

            <a href="{{ route('accounting.purchase-invoices.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.purchase-invoices.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="12" y1="18" x2="12" y2="12"/>
                    <line x1="9" y1="15" x2="15" y2="15"/>
                </svg>
                فواتير الشراء
            </a>

            <a href="{{ route('accounting.purchase-payments.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.purchase-payments.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                    <line x1="1" y1="10" x2="23" y2="10"/>
                    <polyline points="15,14 17,16 21,12"/>
                </svg>
                مدفوعات الموردين
            </a>
        </div>

        {{-- التخطيط المالي --}}
        <div class="ac-sidebar__section">
            <span class="ac-sidebar__section-title">التخطيط المالي</span>

            <a href="{{ route('accounting.budget.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.budget.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/>
                </svg>
                الميزانية التقديرية
            </a>

            <a href="{{ route('accounting.recurring.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.recurring.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="17,1 21,5 17,9"/>
                    <path d="M3 11V9a4 4 0 014-4h14"/>
                    <polyline points="7,23 3,19 7,15"/>
                    <path d="M21 13v2a4 4 0 01-4 4H3"/>
                </svg>
                القيود المتكررة
            </a>
        </div>

        {{-- الموارد البشرية --}}
        <div class="ac-sidebar__section">
            <span class="ac-sidebar__section-title">الموارد البشرية</span>

            <a href="{{ route('accounting.employees.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.employees.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                    <path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
                الموظفون
            </a>

            <a href="{{ route('accounting.payroll.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.payroll.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                    <line x1="12" y1="12" x2="12" y2="16"/>
                    <line x1="10" y1="14" x2="14" y2="14"/>
                </svg>
                مسير الرواتب
            </a>

            <a href="{{ route('accounting.leaves.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.leaves.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                    <path d="M9 16l2 2 4-4"/>
                </svg>
                طلبات الإجازة
            </a>
        </div>

        {{-- الكتالوج والمخزون --}}
        <div class="ac-sidebar__section">
            <span class="ac-sidebar__section-title">الكتالوج والمخزون</span>

            <a href="{{ route('accounting.products.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.products.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                    <polyline points="3.27,6.96 12,12.01 20.73,6.96"/>
                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                </svg>
                المنتجات والخدمات
            </a>

            <a href="{{ route('accounting.inventory.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.inventory.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                المخزون
            </a>
        </div>

        {{-- البنك --}}
        <div class="ac-sidebar__section">
            <span class="ac-sidebar__section-title">البنك</span>

            <a href="{{ route('accounting.bank-reconciliation.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.bank-reconciliation.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
                التسوية البنكية
            </a>
        </div>

        {{-- الأصول --}}
        <div class="ac-sidebar__section">
            <span class="ac-sidebar__section-title">الأصول</span>

            <a href="{{ route('accounting.assets.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.assets.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                    <line x1="12" y1="12" x2="12" y2="16"/>
                    <line x1="10" y1="14" x2="14" y2="14"/>
                </svg>
                الأصول الثابتة
            </a>
        </div>

        {{-- التقارير --}}
        <div class="ac-sidebar__section">
            <span class="ac-sidebar__section-title">التقارير</span>

            <a href="{{ route('accounting.reports.income-expense') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.reports.income-expense') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19h16"/>
                    <path d="M7 16l3-5 3 2 4-6"/>
                    <path d="M17 7h3v3"/>
                </svg>
                الدخل والمصروف
            </a>

            <a href="{{ route('accounting.reports.trial-balance') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.reports.trial-balance') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <path d="M6 20V10l6-6 6 6v10"/>
                </svg>
                ميزان المراجعة
            </a>

            <a href="{{ route('accounting.reports.profit-loss') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.reports.profit-loss') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"/>
                    <polyline points="17,6 23,6 23,12"/>
                </svg>
                الأرباح والخسائر
            </a>

            <a href="{{ route('accounting.reports.cashier-sales') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.reports.cashier-sales') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2h12"/>
                    <path d="M7 6h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/>
                    <path d="M9 11h6"/>
                    <path d="M9 15h4"/>
                </svg>
                مبيعات الكاشير
            </a>

            <a href="{{ route('accounting.reports.balance-sheet') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.reports.balance-sheet') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                    <line x1="12" y1="12" x2="12" y2="16"/>
                    <line x1="10" y1="14" x2="14" y2="14"/>
                </svg>
                الميزانية العمومية
            </a>

            <a href="{{ route('accounting.reports.ar-aging') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.reports.ar-aging') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12,6 12,12 16,14"/>
                </svg>
                تقادم الذمم المدينة
            </a>

            <a href="{{ route('accounting.reports.ap-aging') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.reports.ap-aging') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12,6 12,12 8,14"/>
                </svg>
                تقادم الذمم الدائنة
            </a>

            <a href="{{ route('accounting.reports.vat-report') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.reports.vat-report') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 14L15 8"/>
                    <circle cx="9.5" cy="8.5" r="1.5"/>
                    <circle cx="14.5" cy="13.5" r="1.5"/>
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                </svg>
                تقرير ضريبة القيمة المضافة
            </a>
        </div>

        {{-- الإدارة (مدير فقط) --}}
        @if(auth()->user()?->isAdmin())
        <div class="ac-sidebar__section">
            <span class="ac-sidebar__section-title">الإدارة</span>

            <a href="{{ route('accounting.settings.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.settings.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                </svg>
                إعدادات الشركة
            </a>

            <a href="{{ route('accounting.users.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.users.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                    <path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
                المستخدمون
            </a>

            <a href="{{ route('accounting.fiscal-years.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('accounting.fiscal-years.*') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                    <path d="M9 16l2 2 4-4"/>
                </svg>
                إغلاق السنة المالية
            </a>
        </div>
        @endif

    </nav>

    {{-- User + Logout --}}
    <div class="ac-sidebar__footer">
        <div class="ac-sidebar__user">
            <div class="ac-sidebar__avatar ac-sidebar__avatar--{{ auth()->user()?->roleClass() ?? 'viewer' }}">
                {{ mb_substr(auth()->user()->name ?? 'م', 0, 1) }}
            </div>
            <div class="ac-sidebar__user-info">
                <div class="ac-sidebar__user-name">{{ auth()->user()->name ?? 'المستخدم' }}</div>
                <div class="ac-sidebar__user-role">
                    <span class="ac-role-badge ac-role-badge--sm ac-role-badge--{{ auth()->user()?->roleClass() ?? 'viewer' }}">
                        {{ auth()->user()?->roleName() ?? 'مشاهد' }}
                    </span>
                </div>
            </div>
        </div>
        <form method="POST" action="/dev/logout">
            @csrf
            <button type="submit" class="ac-sidebar__logout" title="تسجيل الخروج">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                    <polyline points="16,17 21,12 16,7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </button>
        </form>
    </div>

</aside>

{{-- ══════════════════════════════════════════════════════════ MAIN --}}
<div class="ac-main">

    <header class="ac-topbar">
        <h1 class="ac-topbar__title">@yield('title', 'الرئيسية')</h1>
        <div>@yield('topbar-actions')</div>
    </header>

    <div class="ac-page">
        @include('accounting._flash')
        @yield('content')
    </div>

</div>

<script src="{{ asset('js/accounting.js') }}" defer></script>
@stack('scripts')
</body>
</html>

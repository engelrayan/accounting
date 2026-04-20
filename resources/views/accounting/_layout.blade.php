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
<body class="ac-app" data-sidebar-state>

@php
    $sidebarModules = collect(getCompanyModules())
        ->filter(fn (array $module) => $module['is_enabled'])
        ->filter(fn (array $module) => ($module['show_in_sidebar'] ?? true) !== false)
        ->values();

    $groupedSidebarModules = $sidebarModules->groupBy(fn (array $module) => $module['section'] ?? '__root');

    $activeSidebarModule = $sidebarModules->first(
        fn (array $module) => request()->routeIs(...($module['active_routes'] ?? [$module['route']]))
    );
@endphp

<div class="ac-sidebar-overlay" data-sidebar-overlay aria-hidden="true"></div>

<aside class="ac-sidebar" id="accounting-sidebar" aria-label="القائمة الجانبية">
    <div class="ac-sidebar__brand">
        <svg class="ac-sidebar__brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
            <path d="M2 17l10 5 10-5"/>
            <path d="M2 12l10 5 10-5"/>
        </svg>
        <span>محاسب عام</span>
        <button type="button"
                class="ac-sidebar__close"
                data-sidebar-close
                aria-label="إغلاق القائمة">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <nav class="ac-sidebar__nav">
        @foreach($groupedSidebarModules as $section => $modules)
            <div class="ac-sidebar__section">
                @if($section !== '__root')
                    <span class="ac-sidebar__section-title">{{ $section }}</span>
                @endif

                @foreach($modules as $module)
                    <a href="{{ route($module['route']) }}"
                       class="ac-sidebar__link {{ request()->routeIs(...($module['active_routes'] ?? [$module['route']])) ? 'ac-sidebar__link--active' : '' }}">
                        @include('accounting.partials.module-icon', ['icon' => $module['icon']])
                        <span>{{ $module['label'] }}</span>
                    </a>
                @endforeach
            </div>
            @endforeach

        @if(auth()->user()?->isAdmin())
            <div class="ac-sidebar__section">
                <span class="ac-sidebar__section-title">الإدارة</span>

                <a href="{{ route('accounting.settings.index') }}"
                   class="ac-sidebar__link {{ request()->routeIs('accounting.settings.index') ? 'ac-sidebar__link--active' : '' }}">
                    @include('accounting.partials.module-icon', ['icon' => 'settings'])
                    <span>إعدادات الشركة</span>
                </a>

                <a href="{{ route('accounting.settings.modules.index') }}"
                   class="ac-sidebar__link {{ request()->routeIs('accounting.settings.modules.*') ? 'ac-sidebar__link--active' : '' }}">
                    @include('accounting.partials.module-icon', ['icon' => 'modules'])
                    <span>تخصيص القائمة</span>
                </a>

                <a href="{{ route('accounting.users.index') }}"
                   class="ac-sidebar__link {{ request()->routeIs('accounting.users.*') ? 'ac-sidebar__link--active' : '' }}">
                    @include('accounting.partials.module-icon', ['icon' => 'users'])
                    <span>المستخدمون</span>
                </a>

                <a href="{{ route('accounting.fiscal-years.index') }}"
                   class="ac-sidebar__link {{ request()->routeIs('accounting.fiscal-years.*') ? 'ac-sidebar__link--active' : '' }}">
                    @include('accounting.partials.module-icon', ['icon' => 'calendar'])
                    <span>إغلاق السنة المالية</span>
                </a>
            </div>
        @endif
    </nav>

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

<div class="ac-main">
    <header class="ac-topbar">
        <button type="button"
                class="ac-sidebar-toggle"
                data-sidebar-toggle
                aria-controls="accounting-sidebar"
                aria-expanded="true"
                aria-label="فتح أو إغلاق القائمة الجانبية">
            <svg class="ac-sidebar-toggle__menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                <line x1="4" y1="6" x2="20" y2="6"/>
                <line x1="4" y1="12" x2="20" y2="12"/>
                <line x1="4" y1="18" x2="20" y2="18"/>
            </svg>
            <svg class="ac-sidebar-toggle__collapse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                <polyline points="15,18 9,12 15,6"/>
            </svg>
        </button>
        <h1 class="ac-topbar__title">{{ $activeSidebarModule['label'] ?? $__env->yieldContent('title', 'الرئيسية') }}</h1>
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

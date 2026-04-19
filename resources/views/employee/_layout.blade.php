<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'بوابة الموظف') — محاسب عام</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/accounting.css') }}">
</head>
<body class="ac-app">

<aside class="ac-sidebar">

    <div class="ac-sidebar__brand">
        <svg class="ac-sidebar__brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
            <path d="M2 17l10 5 10-5"/>
            <path d="M2 12l10 5 10-5"/>
        </svg>
        <span>بوابة الموظف</span>
    </div>

    <nav class="ac-sidebar__nav">

        <div class="ac-sidebar__section">
            <a href="{{ route('employee.dashboard') }}"
               class="ac-sidebar__link {{ request()->routeIs('employee.dashboard') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                الرئيسية
            </a>
        </div>

        <div class="ac-sidebar__section">
            <span class="ac-sidebar__section-title">حسابي</span>

            <a href="{{ route('employee.profile') }}"
               class="ac-sidebar__link {{ request()->routeIs('employee.profile') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                ملفي الشخصي
            </a>

            <a href="{{ route('employee.leaves.index') }}"
               class="ac-sidebar__link {{ request()->routeIs('employee.leaves.index') || request()->routeIs('employee.leaves.show') || request()->routeIs('employee.leaves.create') ? 'ac-sidebar__link--active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                    <path d="M9 16l2 2 4-4"/>
                </svg>
                إجازاتي
            </a>
        </div>

        @php $authEmployee = Auth::guard('employee')->user(); @endphp
        @if($authEmployee && $authEmployee->isManager())
        @php $pendingTeam = $authEmployee->pendingTeamLeavesCount(); @endphp
        <div class="ac-sidebar__section">
            <span class="ac-sidebar__section-title">إدارة الفريق</span>

            <a href="{{ route('employee.leaves.team') }}"
               class="ac-sidebar__link {{ request()->routeIs('employee.leaves.team') ? 'ac-sidebar__link--active' : '' }}"
               style="position:relative;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                    <path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
                طلبات فريقي
                @if($pendingTeam > 0)
                <span style="margin-right:auto;background:#ef4444;color:#fff;font-size:.68rem;font-weight:700;padding:.1rem .45rem;border-radius:20px;min-width:18px;text-align:center;">
                    {{ $pendingTeam }}
                </span>
                @endif
            </a>
        </div>
        @endif

    </nav>

    <div class="ac-sidebar__footer">
        <div class="ac-sidebar__user">
            <div class="ac-sidebar__avatar ac-sidebar__avatar--viewer">
                {{ mb_substr(Auth::guard('employee')->user()->name ?? 'م', 0, 1) }}
            </div>
            <div class="ac-sidebar__user-info">
                <div class="ac-sidebar__user-name">{{ Auth::guard('employee')->user()->name ?? '' }}</div>
                <div class="ac-sidebar__user-role">
                    <span class="ac-role-badge ac-role-badge--sm ac-role-badge--viewer">موظف</span>
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('employee.logout') }}">
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
        <h1 class="ac-topbar__title">@yield('title', 'الرئيسية')</h1>
        <div>@yield('topbar-actions')</div>
    </header>

    <div class="ac-page">
        @if(session('success'))
            <div class="ac-alert ac-alert--success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="ac-alert ac-alert--danger">
                <ul style="margin:0;padding-right:1rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </div>

</div>

<script src="{{ asset('js/accounting.js') }}" defer></script>
@stack('scripts')
</body>
</html>

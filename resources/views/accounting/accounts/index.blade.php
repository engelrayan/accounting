@extends('accounting._layout')

@section('title', 'الحسابات')

@php
$categoryMeta = [
    'asset'     => ['label' => 'الأصول',        'icon' => 'M3 6l3 1m0 0l-3 9a5 5 0 006.9.9L6 7m0-1l3-1m0 0l3 1m-3-1v9m0 9l3-1m0 0l-3-9a5 5 0 00-6.9-.9L18 7m0 1l-3 1m0 0l-3-1m3 1v9', 'color' => 'blue'],
    'liability' => ['label' => 'الالتزامات',    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'color' => 'red'],
    'equity'    => ['label' => 'حقوق الملكية', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0', 'color' => 'amber'],
    'revenue'   => ['label' => 'الإيرادات',     'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',  'color' => 'green'],
    'expense'   => ['label' => 'المصروفات',     'icon' => 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6', 'color' => 'purple'],
];
@endphp

@section('topbar-actions')
    <a href="{{ route('accounting.accounts.create') }}" class="ac-btn ac-btn--primary ac-btn--sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        حساب جديد
    </a>
@endsection

@section('content')

{{-- Global account error (e.g. delete blocked) --}}
@if($errors->has('account'))
    <div class="ac-alert ac-alert--error" style="margin-bottom:16px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        {{ $errors->first('account') }}
    </div>
@endif

@if($grouped->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14,2 14,8 20,8"/>
        </svg>
        <p>لا توجد حسابات بعد.</p>
        <a href="{{ route('accounting.accounts.create') }}" class="ac-btn ac-btn--primary">إضافة أول حساب</a>
    </div>
@else

<div class="ac-accounts-grid">
    @foreach($grouped as $type => $accounts)
    @php
        $meta     = $categoryMeta[$type];
        $total    = $accounts->sum('balance');
        $active   = $accounts->where('is_active', true)->count();
        $inactive = $accounts->where('is_active', false)->count();
    @endphp

    <div class="ac-accounts-card" data-card="{{ $type }}">

        {{-- ── Card header ────────────────────────────────────────────── --}}
        <div class="ac-accounts-card__header ac-accounts-card__header--{{ $meta['color'] }}">
            <div class="ac-accounts-card__header-left">
                <button class="ac-accounts-card__collapse-btn" title="طي / توسيع">
                    <svg class="ac-chevron" width="16" height="16" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="6,9 12,15 18,9"/>
                    </svg>
                </button>
                <div class="ac-accounts-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="{{ $meta['icon'] }}"/>
                    </svg>
                </div>
                <span class="ac-accounts-card__title">{{ $meta['label'] }}</span>
                @if($inactive > 0)
                    <span class="ac-card-inactive-badge">{{ $inactive }} موقوف</span>
                @endif
            </div>
            <div class="ac-accounts-card__total">{{ number_format($total, 2) }}</div>
        </div>

        {{-- ── Account rows ────────────────────────────────────────────── --}}
        <ul class="ac-accounts-list">
            @foreach($accounts as $account)
            <li class="ac-accounts-list__row
                       {{ !$account->is_active ? 'ac-accounts-list__row--inactive' : '' }}
                       {{ $account->parent_id  ? 'ac-accounts-list__row--child'    : '' }}">

                {{-- Info column --}}
                <div class="ac-accounts-list__info">
                    <div class="ac-accounts-list__name">
                        @if($account->parent_id)
                            <svg class="ac-child-arrow" width="10" height="10" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        @endif
                        {{ $account->name }}
                        @if(!$account->is_active)
                            <span class="ac-badge ac-badge--off">موقوف</span>
                        @endif
                        @if($account->is_system)
                            <span class="ac-badge ac-badge--sys">نظام</span>
                        @endif
                    </div>
                    <div class="ac-accounts-list__meta">
                        <code class="ac-code-tag">{{ $account->code }}</code>
                        @if($account->parent)
                            <span class="ac-accounts-list__meta-item">{{ $account->parent->name }}</span>
                        @endif
                    </div>
                </div>

                {{-- Balance + actions column --}}
                <div class="ac-accounts-list__right">
                    <span class="ac-accounts-list__balance {{ $account->balance < 0 ? 'ac-text-danger' : '' }}">
                        {{ number_format($account->balance, 2) }}
                    </span>

                    <div class="ac-accounts-list__actions">

                        {{-- Edit name --}}
                        <a href="{{ route('accounting.accounts.edit', $account) }}"
                           class="ac-icon-btn" title="تعديل الاسم">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </a>

                        {{-- Add child account --}}
                        <a href="{{ route('accounting.accounts.create', ['parent_id' => $account->id]) }}"
                           class="ac-icon-btn ac-icon-btn--add" title="إضافة حساب فرعي">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </a>

                        {{-- Toggle active / inactive --}}
                        @if(!$account->is_system)
                        <form method="POST"
                              action="{{ route('accounting.accounts.toggle-active', $account) }}"
                              class="ac-inline-form">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="ac-icon-btn {{ $account->is_active ? 'ac-icon-btn--deactivate' : 'ac-icon-btn--activate' }}"
                                    title="{{ $account->is_active ? 'إيقاف الحساب' : 'تفعيل الحساب' }}">
                                @if($account->is_active)
                                    {{-- Pause / minus circle --}}
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="8" y1="12" x2="16" y2="12"/>
                                    </svg>
                                @else
                                    {{-- Resume / plus circle --}}
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="12" y1="8" x2="12" y2="16"/>
                                        <line x1="8" y1="12" x2="16" y2="12"/>
                                    </svg>
                                @endif
                            </button>
                        </form>
                        @endif

                        {{-- Delete — only shown when safe (no transactions, no children) --}}
                        @if($account->deletable)
                        <form method="POST"
                              action="{{ route('accounting.accounts.destroy', $account) }}"
                              class="ac-inline-form"
                              data-confirm="هل أنت متأكد من حذف الحساب «{{ $account->name }}»؟ لا يمكن التراجع عن هذا الإجراء.">
                            @csrf @method('DELETE')
                            <button type="submit" class="ac-icon-btn ac-icon-btn--delete" title="حذف الحساب">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3,6 5,6 21,6"/>
                                    <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                    <path d="M10 11v6M14 11v6"/>
                                    <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                                </svg>
                            </button>
                        </form>
                        @endif

                    </div>
                </div>

            </li>
            @endforeach
        </ul>

        {{-- ── Card footer ────────────────────────────────────────────── --}}
        <div class="ac-accounts-card__footer">
            <a href="{{ route('accounting.accounts.create') }}?type={{ $type }}"
               class="ac-btn ac-btn--ghost ac-btn--sm">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                إضافة حساب
            </a>
            <span class="ac-text-sm ac-text-muted">
                {{ $active }} نشط@if($inactive > 0) · <span class="ac-text-warning">{{ $inactive }} موقوف</span>@endif
            </span>
        </div>

    </div>
    @endforeach
</div>

@endif
@endsection

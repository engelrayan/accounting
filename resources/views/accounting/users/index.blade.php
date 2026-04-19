@extends('accounting._layout')

@section('title', 'إدارة المستخدمين')

@section('topbar-actions')
    <a href="{{ route('accounting.users.create') }}" class="ac-btn ac-btn--primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        مستخدم جديد
    </a>
@endsection

@section('content')

@if($errors->has('user'))
    <div class="ac-alert ac-alert--danger">
        {{ $errors->first('user') }}
        <button data-dismiss="alert" class="ac-alert__close">✕</button>
    </div>
@endif

@if(session('success'))
    <div class="ac-alert ac-alert--success">
        {{ session('success') }}
        <button data-dismiss="alert" class="ac-alert__close">✕</button>
    </div>
@endif

@if($users->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 00-3-3.87"/>
            <path d="M16 3.13a4 4 0 010 7.75"/>
        </svg>
        <p>لا يوجد مستخدمون بعد.</p>
        <a href="{{ route('accounting.users.create') }}" class="ac-btn ac-btn--primary">أضف أول مستخدم</a>
    </div>
@else

<div class="ac-card">
    <div class="ac-card__body" style="padding:0;">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>البريد الإلكتروني</th>
                    <th>الدور</th>
                    <th>الصلاحيات</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr class="{{ $user->id === auth()->id() ? 'ac-table__row--highlight' : '' }}">
                    <td>
                        <div class="ac-user-cell">
                            <div class="ac-user-cell__avatar ac-user-cell__avatar--{{ $user->roleClass() }}">
                                {{ mb_substr($user->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="ac-user-cell__name">{{ $user->name }}</div>
                                @if($user->id === auth()->id())
                                    <div class="ac-user-cell__you">أنت</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="ac-table__muted">{{ $user->email }}</td>
                    <td>
                        <span class="ac-role-badge ac-role-badge--{{ $user->roleClass() }}">
                            {{ $user->roleName() }}
                        </span>
                    </td>
                    <td>
                        <div class="ac-permissions-list">
                            @if($user->isAdmin())
                                <span class="ac-perm ac-perm--yes">وصول كامل</span>
                            @elseif($user->isAccountant())
                                <span class="ac-perm ac-perm--yes">إنشاء معاملات</span>
                                <span class="ac-perm ac-perm--yes">عرض التقارير</span>
                            @else
                                <span class="ac-perm ac-perm--no">قراءة فقط</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="ac-row-actions">
                            <a href="{{ route('accounting.users.edit', $user) }}"
                               class="ac-icon-btn" title="تعديل">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </a>
                            @if($user->id !== auth()->id())
                                <form method="POST"
                                      action="{{ route('accounting.users.destroy', $user) }}"
                                      data-confirm="هل أنت متأكد من حذف المستخدم [{{ $user->name }}]؟">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ac-icon-btn ac-icon-btn--delete" title="حذف">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3,6 5,6 21,6"/>
                                            <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                            <path d="M10 11v6"/><path d="M14 11v6"/>
                                            <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Role legend --}}
<div class="ac-role-legend">
    <div class="ac-role-legend__item">
        <span class="ac-role-badge ac-role-badge--admin">مدير</span>
        <span>وصول كامل — إدارة المستخدمين، حذف الحسابات، جميع العمليات</span>
    </div>
    <div class="ac-role-legend__item">
        <span class="ac-role-badge ac-role-badge--accountant">محاسب</span>
        <span>إنشاء المعاملات والقيود وعرض التقارير — لا يمكنه حذف الحسابات</span>
    </div>
    <div class="ac-role-legend__item">
        <span class="ac-role-badge ac-role-badge--viewer">مشاهد</span>
        <span>قراءة فقط — لا يمكنه إنشاء أي معاملات</span>
    </div>
</div>

@endif
@endsection

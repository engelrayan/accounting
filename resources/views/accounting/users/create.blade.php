@extends('accounting._layout')

@section('title', 'مستخدم جديد')

@section('topbar-actions')
    <a href="{{ route('accounting.users.index') }}" class="ac-btn ac-btn--secondary ac-btn--sm">
        ← المستخدمون
    </a>
@endsection

@section('content')

<div class="ac-form-card">

    {{-- Header --}}
    <div class="ac-form-card__header">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
            <line x1="19" y1="8" x2="19" y2="14"/>
            <line x1="22" y1="11" x2="16" y2="11"/>
        </svg>
        إضافة مستخدم جديد
    </div>

    <form method="POST" action="{{ route('accounting.users.store') }}" class="ac-form-card__body">
        @csrf

        {{-- Name --}}
        <div class="ac-form-group">
            <label class="ac-label" for="name">الاسم الكامل <span class="ac-required">*</span></label>
            <input id="name" name="name" type="text" class="ac-control @error('name') ac-control--error @enderror"
                   value="{{ old('name') }}" placeholder="مثال: أحمد محمد" autofocus>
            @error('name')
                <span class="ac-field-error">{{ $message }}</span>
            @enderror
        </div>

        {{-- Email --}}
        <div class="ac-form-group">
            <label class="ac-label" for="email">البريد الإلكتروني <span class="ac-required">*</span></label>
            <input id="email" name="email" type="email" class="ac-control @error('email') ac-control--error @enderror"
                   value="{{ old('email') }}" placeholder="example@company.com" dir="ltr">
            @error('email')
                <span class="ac-field-error">{{ $message }}</span>
            @enderror
        </div>

        {{-- Role --}}
        <div class="ac-form-group">
            <label class="ac-label">الدور الوظيفي <span class="ac-required">*</span></label>
            <div class="ac-role-picker">

                <label class="ac-role-option {{ old('role') === 'admin' ? 'ac-role-option--selected' : '' }}">
                    <input type="radio" name="role" value="admin" {{ old('role') === 'admin' ? 'checked' : '' }}>
                    <div class="ac-role-option__icon ac-role-option__icon--admin">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <div class="ac-role-option__body">
                        <span class="ac-role-option__name">مدير</span>
                        <span class="ac-role-option__desc">وصول كامل — حذف الحسابات، إدارة المستخدمين</span>
                    </div>
                </label>

                <label class="ac-role-option {{ old('role', 'accountant') === 'accountant' ? 'ac-role-option--selected' : '' }}">
                    <input type="radio" name="role" value="accountant" {{ old('role', 'accountant') === 'accountant' ? 'checked' : '' }}>
                    <div class="ac-role-option__icon ac-role-option__icon--accountant">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14,2 14,8 20,8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </div>
                    <div class="ac-role-option__body">
                        <span class="ac-role-option__name">محاسب</span>
                        <span class="ac-role-option__desc">إنشاء المعاملات والقيود — لا يمكنه حذف الحسابات</span>
                    </div>
                </label>

                <label class="ac-role-option {{ old('role') === 'viewer' ? 'ac-role-option--selected' : '' }}">
                    <input type="radio" name="role" value="viewer" {{ old('role') === 'viewer' ? 'checked' : '' }}>
                    <div class="ac-role-option__icon ac-role-option__icon--viewer">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div class="ac-role-option__body">
                        <span class="ac-role-option__name">مشاهد</span>
                        <span class="ac-role-option__desc">قراءة فقط — لا يمكنه إنشاء أي معاملات</span>
                    </div>
                </label>

            </div>
            @error('role')
                <span class="ac-field-error">{{ $message }}</span>
            @enderror
        </div>

        {{-- Password --}}
        <div class="ac-form-row">
            <div class="ac-form-group">
                <label class="ac-label" for="password">كلمة المرور <span class="ac-required">*</span></label>
                <input id="password" name="password" type="password"
                       class="ac-control @error('password') ac-control--error @enderror"
                       placeholder="8 أحرف على الأقل" dir="ltr">
                @error('password')
                    <span class="ac-field-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="ac-form-group">
                <label class="ac-label" for="password_confirmation">تأكيد كلمة المرور <span class="ac-required">*</span></label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       class="ac-control" placeholder="أعد كتابة كلمة المرور" dir="ltr">
            </div>
        </div>

        <div class="ac-form-actions">
            <button type="submit" class="ac-btn ac-btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                إنشاء المستخدم
            </button>
            <a href="{{ route('accounting.users.index') }}" class="ac-btn ac-btn--secondary">إلغاء</a>
        </div>

    </form>
</div>

@endsection

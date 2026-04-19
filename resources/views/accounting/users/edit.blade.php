@extends('accounting._layout')

@section('title', 'تعديل المستخدم')

@section('topbar-actions')
    <a href="{{ route('accounting.users.index') }}" class="ac-btn ac-btn--secondary ac-btn--sm">
        ← المستخدمون
    </a>
@endsection

@section('content')

<div class="ac-form-card">

    <div class="ac-form-card__header">
        <div class="ac-user-cell__avatar ac-user-cell__avatar--{{ $user->roleClass() }}" style="width:36px;height:36px;font-size:16px;">
            {{ mb_substr($user->name, 0, 1) }}
        </div>
        تعديل: {{ $user->name }}
    </div>

    <form method="POST" action="{{ route('accounting.users.update', $user) }}" class="ac-form-card__body">
        @csrf @method('PUT')

        {{-- Name --}}
        <div class="ac-form-group">
            <label class="ac-label" for="name">الاسم الكامل <span class="ac-required">*</span></label>
            <input id="name" name="name" type="text"
                   class="ac-control @error('name') ac-control--error @enderror"
                   value="{{ old('name', $user->name) }}" autofocus>
            @error('name')
                <span class="ac-field-error">{{ $message }}</span>
            @enderror
        </div>

        {{-- Email --}}
        <div class="ac-form-group">
            <label class="ac-label" for="email">البريد الإلكتروني <span class="ac-required">*</span></label>
            <input id="email" name="email" type="email" dir="ltr"
                   class="ac-control @error('email') ac-control--error @enderror"
                   value="{{ old('email', $user->email) }}">
            @error('email')
                <span class="ac-field-error">{{ $message }}</span>
            @enderror
        </div>

        {{-- Role --}}
        <div class="ac-form-group">
            <label class="ac-label">الدور الوظيفي <span class="ac-required">*</span></label>
            <div class="ac-role-picker">

                @foreach(['admin' => ['مدير', 'وصول كامل — حذف الحسابات، إدارة المستخدمين', 'admin'], 'accountant' => ['محاسب', 'إنشاء المعاملات والقيود — لا يمكنه حذف الحسابات', 'accountant'], 'viewer' => ['مشاهد', 'قراءة فقط — لا يمكنه إنشاء أي معاملات', 'viewer']] as $roleVal => [$roleLabel, $roleDesc, $roleClass])
                @php $selected = old('role', $user->role) === $roleVal; @endphp
                <label class="ac-role-option {{ $selected ? 'ac-role-option--selected' : '' }}">
                    <input type="radio" name="role" value="{{ $roleVal }}" {{ $selected ? 'checked' : '' }}>
                    <div class="ac-role-option__icon ac-role-option__icon--{{ $roleClass }}">
                        @if($roleClass === 'admin')
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        @elseif($roleClass === 'accountant')
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14,2 14,8 20,8"/>
                            </svg>
                        @else
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        @endif
                    </div>
                    <div class="ac-role-option__body">
                        <span class="ac-role-option__name">{{ $roleLabel }}</span>
                        <span class="ac-role-option__desc">{{ $roleDesc }}</span>
                    </div>
                </label>
                @endforeach

            </div>
            @error('role')
                <span class="ac-field-error">{{ $message }}</span>
            @enderror
        </div>

        {{-- Password (optional) --}}
        <div class="ac-form-group">
            <label class="ac-label" for="password">
                كلمة مرور جديدة
                <span class="ac-hint">اتركها فارغة إذا لم تريد التغيير</span>
            </label>
            <div class="ac-form-row">
                <input id="password" name="password" type="password"
                       class="ac-control @error('password') ac-control--error @enderror"
                       placeholder="كلمة مرور جديدة" dir="ltr">
                <input name="password_confirmation" type="password"
                       class="ac-control" placeholder="تأكيد كلمة المرور الجديدة" dir="ltr">
            </div>
            @error('password')
                <span class="ac-field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="ac-form-actions">
            <button type="submit" class="ac-btn ac-btn--primary">حفظ التغييرات</button>
            <a href="{{ route('accounting.users.index') }}" class="ac-btn ac-btn--secondary">إلغاء</a>
        </div>

    </form>
</div>

@endsection

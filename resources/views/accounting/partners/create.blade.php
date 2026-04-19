@extends('accounting._layout')

@section('title', 'إضافة شريك')

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">إضافة شريك جديد</h1>
    <div class="ac-page-header__actions">
        <a href="{{ route('accounting.partners.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
    </div>
</div>

@if($errors->has('partner'))
    <div class="ac-alert ac-alert--error">{{ $errors->first('partner') }}</div>
@endif

<div class="ac-card">
    <div class="ac-card__body">

        <p class="ac-text-muted ac-text-sm" style="">
            سيتم إنشاء حساب رأس مال وحساب مسحوبات تلقائياً لهذا الشريك.
        </p>

        <hr class="ac-divider">

        <form method="POST" action="{{ route('accounting.partners.store') }}">
            @csrf

            <div class="ac-form-row">
                <div class="ac-form-group">
                    <label class="ac-label ac-label--required" for="name">اسم الشريك</label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           class="ac-control {{ $errors->has('name') ? 'ac-control--error' : '' }}"
                           placeholder="مثال: محمود أحمد"
                           required>
                    @error('name')
                        <div class="ac-field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="phone">رقم الهاتف</label>
                    <input type="text"
                           id="phone"
                           name="phone"
                           value="{{ old('phone') }}"
                           class="ac-control {{ $errors->has('phone') ? 'ac-control--error' : '' }}"
                           placeholder="اختياري">
                    @error('phone')
                        <div class="ac-field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="email">البريد الإلكتروني</label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="ac-control {{ $errors->has('email') ? 'ac-control--error' : '' }}"
                           placeholder="اختياري">
                    @error('email')
                        <div class="ac-field-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="ac-form-group">
                <label class="ac-label" for="notes">ملاحظات</label>
                <textarea id="notes"
                          name="notes"
                          class="ac-textarea {{ $errors->has('notes') ? 'ac-control--error' : '' }}"
                          rows="3"
                          placeholder="اختياري">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="ac-field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="ac-form-actions">
                <button type="submit" class="ac-btn ac-btn--primary">إضافة الشريك</button>
                <a href="{{ route('accounting.partners.index') }}" class="ac-btn ac-btn--secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>

@endsection

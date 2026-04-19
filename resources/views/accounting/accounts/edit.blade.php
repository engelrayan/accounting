@extends('accounting._layout')

@section('title', 'تعديل الحساب')

@section('content')
<div class="ac-page-header">
    <h1 class="ac-page-header__title">تعديل الحساب</h1>
    <a href="{{ route('accounting.accounts.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>

@php
$typeLabels = ['asset'=>'أصول','liability'=>'التزامات','equity'=>'حقوق ملكية','revenue'=>'إيرادات','expense'=>'مصروفات'];
@endphp

<div class="ac-card">
    <div class="ac-card__body">

        {{-- Read-only account info ──────────────────────────────────────── --}}
        <div class="ac-parent-info ac-parent-info--readonly">
            <div class="ac-parent-info__row">
                <span class="ac-parent-info__label">الكود</span>
                <code class="ac-code-tag">{{ $account->code }}</code>
            </div>
            <div class="ac-parent-info__row">
                <span class="ac-parent-info__label">النوع</span>
                <span class="ac-parent-info__value">{{ $typeLabels[$account->type] ?? $account->type }}</span>
            </div>
            <div class="ac-parent-info__row">
                <span class="ac-parent-info__label">الرصيد الطبيعي</span>
                <span class="ac-parent-info__value">
                    {{ $account->normal_balance === 'debit' ? 'مدين' : 'دائن' }}
                </span>
            </div>
            @if($account->parent)
            <div class="ac-parent-info__row">
                <span class="ac-parent-info__label">الحساب الأب</span>
                <span class="ac-parent-info__value">
                    <code class="ac-code-tag">{{ $account->parent->code }}</code>
                    {{ $account->parent->name }}
                </span>
            </div>
            @endif
            @if($account->is_system)
            <div class="ac-parent-info__row">
                <span class="ac-parent-info__label">حساب نظام</span>
                <span class="ac-badge ac-badge--sys">لا يمكن إيقافه</span>
            </div>
            @endif
        </div>

        <hr class="ac-divider">

        {{-- Editable fields ─────────────────────────────────────────────── --}}
        <form method="POST" action="{{ route('accounting.accounts.update', $account) }}">
            @csrf
            @method('PUT')

            <div class="ac-form-group">
                <label class="ac-label ac-label--required" for="name">اسم الحساب</label>
                <input id="name" name="name" type="text"
                       class="ac-control {{ $errors->has('name') ? 'ac-control--error' : '' }}"
                       value="{{ old('name', $account->name) }}"
                       autofocus>
                @error('name') <span class="ac-field-error">{{ $message }}</span> @enderror
            </div>

            <div class="ac-form-group">
                <label class="ac-label" for="description">ملاحظات</label>
                <textarea id="description" name="description"
                          class="ac-textarea">{{ old('description', $account->description) }}</textarea>
            </div>

            <div class="ac-form-actions">
                <button type="submit" class="ac-btn ac-btn--primary">حفظ التعديلات</button>
                <a href="{{ route('accounting.accounts.index') }}" class="ac-btn ac-btn--secondary">إلغاء</a>
            </div>
        </form>

    </div>
</div>
@endsection

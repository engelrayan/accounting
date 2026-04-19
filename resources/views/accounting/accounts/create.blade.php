@extends('accounting._layout')

@section('title', $parentAccount ? 'إضافة حساب فرعي' : 'حساب جديد')

@section('content')
<div class="ac-page-header">
    <h1 class="ac-page-header__title">
        @if($parentAccount)
            إضافة حساب فرعي تحت: <span class="ac-page-header__sub">{{ $parentAccount->name }}</span>
        @else
            حساب جديد
        @endif
    </h1>
    <a href="{{ route('accounting.accounts.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>

<div class="ac-card">
    <div class="ac-card__body">
        <form method="POST" action="{{ route('accounting.accounts.store') }}">
            @csrf

            @if($parentAccount)
                {{-- ══ CHILD MODE — simplified ══════════════════════════════ --}}

                <input type="hidden" name="parent_id" value="{{ $parentAccount->id }}">

                {{-- Parent info panel --}}
                <div class="ac-parent-info">
                    <div class="ac-parent-info__row">
                        <span class="ac-parent-info__label">الحساب الأب</span>
                        <span class="ac-parent-info__value">
                            <code class="ac-code-tag">{{ $parentAccount->code }}</code>
                            {{ $parentAccount->name }}
                        </span>
                    </div>
                    <div class="ac-parent-info__row">
                        <span class="ac-parent-info__label">النوع المورّث</span>
                        <span class="ac-parent-info__value">
                            @php
                            $typeLabels = ['asset'=>'أصول','liability'=>'التزامات','equity'=>'حقوق ملكية','revenue'=>'إيرادات','expense'=>'مصروفات'];
                            @endphp
                            {{ $typeLabels[$parentAccount->type] ?? $parentAccount->type }}
                        </span>
                    </div>
                    <div class="ac-parent-info__row">
                        <span class="ac-parent-info__label">الرصيد الطبيعي</span>
                        <span class="ac-parent-info__value">
                            {{ $parentAccount->normal_balance === 'debit' ? 'مدين' : 'دائن' }}
                        </span>
                    </div>
                </div>

                <div class="ac-form-group">
                    <label class="ac-label ac-label--required" for="name">اسم الحساب الفرعي</label>
                    <input id="name" name="name" type="text"
                           class="ac-control {{ $errors->has('name') ? 'ac-control--error' : '' }}"
                           value="{{ old('name') }}"
                           placeholder="مثال: بنك الراجحي"
                           autofocus>
                    @error('name') <span class="ac-field-error">{{ $message }}</span> @enderror
                    <span class="ac-hint">سيُخصَّص كود الحساب تلقائياً.</span>
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="description">ملاحظات</label>
                    <textarea id="description" name="description"
                              class="ac-textarea"
                              placeholder="اختياري">{{ old('description') }}</textarea>
                </div>

            @else
                {{-- ══ STANDALONE MODE — full form ══════════════════════════ --}}

                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="code">كود الحساب</label>
                        <input id="code" name="code" type="text"
                               class="ac-control {{ $errors->has('code') ? 'ac-control--error' : '' }}"
                               value="{{ old('code') }}"
                               placeholder="مثال: 1100">
                        @error('code') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="name">اسم الحساب</label>
                        <input id="name" name="name" type="text"
                               class="ac-control {{ $errors->has('name') ? 'ac-control--error' : '' }}"
                               value="{{ old('name') }}"
                               placeholder="مثال: الأصول المتداولة">
                        @error('name') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="account-type">نوع الحساب</label>
                        <select id="account-type" name="type"
                                class="ac-select {{ $errors->has('type') ? 'ac-select--error' : '' }}">
                            <option value="">— اختر النوع —</option>
                            <option value="asset"     {{ old('type', request('type')) === 'asset'     ? 'selected' : '' }}>أصول</option>
                            <option value="liability" {{ old('type', request('type')) === 'liability' ? 'selected' : '' }}>التزامات</option>
                            <option value="equity"    {{ old('type', request('type')) === 'equity'    ? 'selected' : '' }}>حقوق ملكية</option>
                            <option value="revenue"   {{ old('type', request('type')) === 'revenue'   ? 'selected' : '' }}>إيرادات</option>
                            <option value="expense"   {{ old('type', request('type')) === 'expense'   ? 'selected' : '' }}>مصروفات</option>
                        </select>
                        @error('type') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="account-normal-balance">الرصيد الطبيعي</label>
                        <select id="account-normal-balance" name="normal_balance"
                                class="ac-select {{ $errors->has('normal_balance') ? 'ac-select--error' : '' }}">
                            <option value="debit"  {{ old('normal_balance', 'debit') === 'debit'  ? 'selected' : '' }}>مدين</option>
                            <option value="credit" {{ old('normal_balance')           === 'credit' ? 'selected' : '' }}>دائن</option>
                        </select>
                        <span class="ac-hint">يُملأ تلقائياً عند اختيار النوع.</span>
                        @error('normal_balance') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="parent_id">الحساب الأب</label>
                    <select id="parent_id" name="parent_id" class="ac-select">
                        <option value="">— بدون (حساب رئيسي) —</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                {{ $parent->code }} — {{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id') <span class="ac-field-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="description">ملاحظات</label>
                    <textarea id="description" name="description"
                              class="ac-textarea">{{ old('description') }}</textarea>
                </div>

            @endif

            <div class="ac-form-actions">
                <button type="submit" class="ac-btn ac-btn--primary">إنشاء الحساب</button>
                <a href="{{ route('accounting.accounts.index') }}" class="ac-btn ac-btn--secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection

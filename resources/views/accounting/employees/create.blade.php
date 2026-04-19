@extends('accounting._layout')

@section('title', 'موظف جديد')

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">إضافة موظف جديد</h1>
    <a href="{{ route('accounting.employees.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>

<form method="POST" action="{{ route('accounting.employees.store') }}">
    @csrf

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

        {{-- ── معلومات أساسية ────────────────────────────────────────────── --}}
        <div class="ac-card">
            <div class="ac-card__header">
                <span class="ac-card__title">البيانات الأساسية</span>
            </div>
            <div class="ac-card__body">

                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="employee_number">رقم الموظف</label>
                        <input id="employee_number" name="employee_number" type="text"
                               class="ac-control {{ $errors->has('employee_number') ? 'ac-control--error' : '' }}"
                               value="{{ old('employee_number', $nextNumber) }}" required>
                        @error('employee_number') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="name">الاسم الكامل</label>
                        <input id="name" name="name" type="text"
                               class="ac-control {{ $errors->has('name') ? 'ac-control--error' : '' }}"
                               value="{{ old('name') }}" required>
                        @error('name') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label" for="national_id">الرقم الوطني / الهوية</label>
                        <input id="national_id" name="national_id" type="text"
                               class="ac-control"
                               value="{{ old('national_id') }}">
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label" for="phone">رقم الهاتف</label>
                        <input id="phone" name="phone" type="text"
                               class="ac-control"
                               value="{{ old('phone') }}">
                    </div>
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="email">البريد الإلكتروني</label>
                    <input id="email" name="email" type="email"
                           class="ac-control {{ $errors->has('email') ? 'ac-control--error' : '' }}"
                           value="{{ old('email') }}">
                    @error('email') <span class="ac-field-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label" for="department">القسم</label>
                        <input id="department" name="department" type="text"
                               class="ac-control"
                               value="{{ old('department') }}" placeholder="مثال: المحاسبة">
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label" for="position">المسمى الوظيفي</label>
                        <input id="position" name="position" type="text"
                               class="ac-control"
                               value="{{ old('position') }}" placeholder="مثال: محاسب">
                    </div>
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="manager_id">المدير المباشر</label>
                    <select id="manager_id" name="manager_id" class="ac-select">
                        <option value="">— بدون مدير مباشر —</option>
                        @foreach($managers as $mgr)
                            <option value="{{ $mgr->id }}" {{ old('manager_id') == $mgr->id ? 'selected' : '' }}>
                                {{ $mgr->name }}
                                @if($mgr->position) ({{ $mgr->position }}) @endif
                            </option>
                        @endforeach
                    </select>
                    <p style="font-size:.78rem;color:var(--ac-text-muted);margin-top:.3rem;">
                        المدير المباشر سيتولى مراجعة طلبات الإجازة
                    </p>
                </div>

                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="hire_date">تاريخ التعيين</label>
                        <input id="hire_date" name="hire_date" type="date"
                               class="ac-control {{ $errors->has('hire_date') ? 'ac-control--error' : '' }}"
                               value="{{ old('hire_date', today()->toDateString()) }}" required>
                        @error('hire_date') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label" for="status">الحالة</label>
                        <select id="status" name="status" class="ac-select">
                            <option value="active"   {{ old('status', 'active') === 'active'   ? 'selected' : '' }}>نشط</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>غير نشط</option>
                        </select>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── معلومات الراتب ─────────────────────────────────────────────── --}}
        <div class="ac-card">
            <div class="ac-card__header">
                <span class="ac-card__title">بيانات الراتب والبنك</span>
            </div>
            <div class="ac-card__body">

                <div class="ac-form-group">
                    <label class="ac-label ac-label--required" for="basic_salary">الراتب الأساسي</label>
                    <input id="basic_salary" name="basic_salary" type="number" step="0.01" min="0"
                           class="ac-control {{ $errors->has('basic_salary') ? 'ac-control--error' : '' }}"
                           value="{{ old('basic_salary', 0) }}" required>
                    @error('basic_salary') <span class="ac-field-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-form-group" style="margin-top:16px">
                    <label class="ac-label" for="bank_account">رقم الحساب البنكي</label>
                    <input id="bank_account" name="bank_account" type="text"
                           class="ac-control"
                           value="{{ old('bank_account') }}">
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="iban">رقم الآيبان (IBAN)</label>
                    <input id="iban" name="iban" type="text"
                           class="ac-control"
                           value="{{ old('iban') }}" placeholder="SA...">
                </div>

                <div style="margin-top:24px;padding:14px;background:var(--ac-bg);border-radius:8px;font-size:.83rem;color:var(--ac-text-muted)">
                    البدلات والخصومات تُضاف عند إنشاء مسير الرواتب الشهري لكل موظف على حدة.
                </div>

            </div>
        </div>

    </div>

    <div style="margin-top:16px;display:flex;gap:10px;justify-content:flex-end">
        <a href="{{ route('accounting.employees.index') }}" class="ac-btn ac-btn--secondary">إلغاء</a>
        <button type="submit" class="ac-btn ac-btn--primary">حفظ الموظف</button>
    </div>
</form>

@endsection

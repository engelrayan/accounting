@extends('accounting._layout')

@section('title', 'تعديل بيانات الموظف')

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">تعديل: {{ $employee->name }}</h1>
    <a href="{{ route('accounting.employees.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>

<form method="POST" action="{{ route('accounting.employees.update', $employee) }}">
    @csrf
    @method('PUT')

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

        <div class="ac-card">
            <div class="ac-card__header"><span class="ac-card__title">البيانات الأساسية</span></div>
            <div class="ac-card__body">

                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="employee_number">رقم الموظف</label>
                        <input id="employee_number" name="employee_number" type="text"
                               class="ac-control {{ $errors->has('employee_number') ? 'ac-control--error' : '' }}"
                               value="{{ old('employee_number', $employee->employee_number) }}" required>
                        @error('employee_number') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="name">الاسم الكامل</label>
                        <input id="name" name="name" type="text"
                               class="ac-control {{ $errors->has('name') ? 'ac-control--error' : '' }}"
                               value="{{ old('name', $employee->name) }}" required>
                        @error('name') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label" for="national_id">الرقم الوطني</label>
                        <input id="national_id" name="national_id" type="text" class="ac-control"
                               value="{{ old('national_id', $employee->national_id) }}">
                    </div>
                    <div class="ac-form-group">
                        <label class="ac-label" for="phone">الهاتف</label>
                        <input id="phone" name="phone" type="text" class="ac-control"
                               value="{{ old('phone', $employee->phone) }}">
                    </div>
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="email">البريد الإلكتروني</label>
                    <input id="email" name="email" type="email" class="ac-control"
                           value="{{ old('email', $employee->email) }}">
                </div>

                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label" for="department">القسم</label>
                        <input id="department" name="department" type="text" class="ac-control"
                               value="{{ old('department', $employee->department) }}">
                    </div>
                    <div class="ac-form-group">
                        <label class="ac-label" for="position">المسمى الوظيفي</label>
                        <input id="position" name="position" type="text" class="ac-control"
                               value="{{ old('position', $employee->position) }}">
                    </div>
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="manager_id">المدير المباشر</label>
                    <select id="manager_id" name="manager_id" class="ac-select">
                        <option value="">— بدون مدير مباشر —</option>
                        @foreach($managers as $mgr)
                            <option value="{{ $mgr->id }}"
                                {{ old('manager_id', $employee->manager_id) == $mgr->id ? 'selected' : '' }}>
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
                        <input id="hire_date" name="hire_date" type="date" class="ac-control"
                               value="{{ old('hire_date', $employee->hire_date->toDateString()) }}" required>
                    </div>
                    <div class="ac-form-group">
                        <label class="ac-label" for="status">الحالة</label>
                        <select id="status" name="status" class="ac-select">
                            <option value="active"   {{ old('status', $employee->status) === 'active'   ? 'selected' : '' }}>نشط</option>
                            <option value="inactive" {{ old('status', $employee->status) === 'inactive' ? 'selected' : '' }}>غير نشط</option>
                        </select>
                    </div>
                </div>

            </div>
        </div>

        <div class="ac-card">
            <div class="ac-card__header"><span class="ac-card__title">بيانات الراتب والبنك</span></div>
            <div class="ac-card__body">

                <div class="ac-form-group">
                    <label class="ac-label ac-label--required" for="basic_salary">الراتب الأساسي</label>
                    <input id="basic_salary" name="basic_salary" type="number" step="0.01" min="0"
                           class="ac-control"
                           value="{{ old('basic_salary', $employee->basic_salary) }}" required>
                </div>

                <div class="ac-form-group" style="margin-top:16px">
                    <label class="ac-label" for="bank_account">رقم الحساب البنكي</label>
                    <input id="bank_account" name="bank_account" type="text" class="ac-control"
                           value="{{ old('bank_account', $employee->bank_account) }}">
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="iban">IBAN</label>
                    <input id="iban" name="iban" type="text" class="ac-control"
                           value="{{ old('iban', $employee->iban) }}">
                </div>

                <div style="margin-top:16px;font-size:.8rem;color:var(--ac-text-muted)">
                    آخر تعديل: {{ $employee->updated_at->format('Y-m-d H:i') }}
                </div>

            </div>
        </div>

    </div>

    <div style="margin-top:16px;display:flex;gap:10px;justify-content:flex-end">
        <a href="{{ route('accounting.employees.index') }}" class="ac-btn ac-btn--secondary">إلغاء</a>
        <button type="submit" class="ac-btn ac-btn--primary">حفظ التعديلات</button>
    </div>
</form>

@endsection

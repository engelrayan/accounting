@extends('accounting._layout')

@section('title', 'مسير رواتب جديد')

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">إنشاء مسير رواتب جديد</h1>
    <a href="{{ route('accounting.payroll.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>

@if($errors->has('run'))
    <div class="ac-alert ac-alert--error">{{ $errors->first('run') }}</div>
@endif

<div style="max-width:480px;margin:0 auto">
    <div class="ac-card">
        <div class="ac-card__body">

            @if($activeCount === 0)
                <div class="ac-alert ac-alert--warning" style="margin-bottom:16px">
                    لا يوجد موظفون نشطون. أضف موظفين أولاً حتى يتم تحميلهم تلقائياً في المسير.
                    <br><a href="{{ route('accounting.employees.create') }}" style="font-weight:600">إضافة موظف →</a>
                </div>
            @else
                <div style="padding:12px;background:var(--ac-bg);border-radius:8px;margin-bottom:20px;font-size:.88rem">
                    سيتم تحميل <strong>{{ $activeCount }}</strong> موظف نشط تلقائياً في المسير.
                </div>
            @endif

            <form method="POST" action="{{ route('accounting.payroll.store') }}">
                @csrf

                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="period_year">السنة</label>
                        <input id="period_year" name="period_year" type="number"
                               min="2000" max="2100"
                               class="ac-control {{ $errors->has('period_year') ? 'ac-control--error' : '' }}"
                               value="{{ old('period_year', $currentYear) }}" required>
                        @error('period_year') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="period_month">الشهر</label>
                        <select id="period_month" name="period_month"
                                class="ac-select {{ $errors->has('period_month') ? 'ac-select--error' : '' }}" required>
                            @php
                                $months = [
                                    1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',
                                    5=>'مايو',6=>'يونيو',7=>'يوليو',8=>'أغسطس',
                                    9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'
                                ];
                            @endphp
                            @foreach($months as $num => $label)
                                <option value="{{ $num }}"
                                    {{ old('period_month', $currentMonth) == $num ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('period_month') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
                    <a href="{{ route('accounting.payroll.index') }}" class="ac-btn ac-btn--secondary">إلغاء</a>
                    <button type="submit" class="ac-btn ac-btn--primary" {{ $activeCount === 0 ? 'disabled' : '' }}>
                        إنشاء المسير
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection

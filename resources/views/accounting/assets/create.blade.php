@extends('accounting._layout')

@section('title', 'إضافة أصل ثابت')

@section('content')
<div class="ac-page-header">
    <h1 class="ac-page-header__title">إضافة أصل ثابت جديد</h1>
    <a href="{{ route('accounting.assets.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>

@if($errors->has('asset'))
    <div class="ac-alert ac-alert--error">{{ $errors->first('asset') }}</div>
@endif

<div class="ac-card">
    <div class="ac-card__body">

        {{-- Info note --}}
        <div class="ac-asset-info-note">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span>سيتم تسجيل قيد شراء الأصل وحساب الإهلاك تلقائيًا بناءً على الفئة المختارة.</span>
        </div>

        <form method="POST" action="{{ route('accounting.assets.store') }}">
            @csrf

            {{-- بيانات الأصل --}}
            <div class="ac-form-row">
                <div class="ac-form-group">
                    <label class="ac-label ac-label--required" for="name">اسم الأصل</label>
                    <input id="name" name="name" type="text"
                           class="ac-control {{ $errors->has('name') ? 'ac-control--error' : '' }}"
                           value="{{ old('name') }}"
                           placeholder="مثال: سيارة توصيل رقم 3">
                    @error('name') <span class="ac-field-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-form-group">
                    <label class="ac-label ac-label--required" for="category">الفئة</label>
                    <select id="category" name="category"
                            class="ac-select {{ $errors->has('category') ? 'ac-select--error' : '' }}">
                        <option value="">— اختر الفئة —</option>
                        <option value="vehicle"   {{ old('category') === 'vehicle'   ? 'selected' : '' }}>🚗 سيارة</option>
                        <option value="equipment" {{ old('category') === 'equipment' ? 'selected' : '' }}>⚙️ معدات</option>
                        <option value="furniture" {{ old('category') === 'furniture' ? 'selected' : '' }}>🪑 أثاث</option>
                        <option value="building"  {{ old('category') === 'building'  ? 'selected' : '' }}>🏢 مبنى</option>
                        <option value="other"     {{ old('category') === 'other'     ? 'selected' : '' }}>📦 أخرى</option>
                    </select>
                    @error('category') <span class="ac-field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="ac-form-row">
                <div class="ac-form-group">
                    <label class="ac-label ac-label--required" for="purchase_date">تاريخ الشراء</label>
                    <input id="purchase_date" name="purchase_date" type="date"
                           class="ac-control {{ $errors->has('purchase_date') ? 'ac-control--error' : '' }}"
                           value="{{ old('purchase_date', date('Y-m-d')) }}">
                    @error('purchase_date') <span class="ac-field-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-form-group">
                    <label class="ac-label ac-label--required" for="purchase_cost">تكلفة الشراء</label>
                    <input id="purchase_cost" name="purchase_cost" type="number" step="0.01" min="0.01"
                           class="ac-control ac-control--num {{ $errors->has('purchase_cost') ? 'ac-control--error' : '' }}"
                           value="{{ old('purchase_cost') }}" placeholder="0.00">
                    @error('purchase_cost') <span class="ac-field-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="salvage_value">القيمة التخريدية</label>
                    <input id="salvage_value" name="salvage_value" type="number" step="0.01" min="0"
                           class="ac-control ac-control--num"
                           value="{{ old('salvage_value', '0') }}" placeholder="0.00">
                    <span class="ac-hint">القيمة المتبقية بعد انتهاء العمر الافتراضي (اختياري).</span>
                </div>

                <div class="ac-form-group">
                    <label class="ac-label ac-label--required" for="useful_life">العمر الافتراضي (شهر)</label>
                    <input id="useful_life" name="useful_life" type="number" min="1"
                           class="ac-control ac-control--num {{ $errors->has('useful_life') ? 'ac-control--error' : '' }}"
                           value="{{ old('useful_life') }}"
                           placeholder="مثال: 60">
                    <span class="ac-hint">60 شهر = 5 سنوات</span>
                    @error('useful_life') <span class="ac-field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <hr class="ac-divider">

            <div class="ac-form-group">
                <label class="ac-label ac-label--required" for="payment_account_id">حساب الدفع</label>
                <select id="payment_account_id" name="payment_account_id"
                        class="ac-select {{ $errors->has('payment_account_id') ? 'ac-select--error' : '' }}">
                    <option value="">— اختر طريقة الدفع —</option>
                    @foreach($paymentAccounts as $acc)
                        <option value="{{ $acc->id }}" {{ old('payment_account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
                <span class="ac-hint">من أي حساب تم الدفع؟ (خزنة أو بنك)</span>
                @error('payment_account_id') <span class="ac-field-error">{{ $message }}</span> @enderror
            </div>

            <div class="ac-form-group">
                <label class="ac-label" for="notes">ملاحظات</label>
                <textarea id="notes" name="notes" class="ac-textarea"
                          placeholder="اختياري">{{ old('notes') }}</textarea>
            </div>

            <div class="ac-form-actions">
                <button type="submit" class="ac-btn ac-btn--primary">حفظ الأصل</button>
                <a href="{{ route('accounting.assets.index') }}" class="ac-btn ac-btn--secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection

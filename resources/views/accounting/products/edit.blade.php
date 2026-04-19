@extends('accounting._layout')

@section('title', 'تعديل: ' . $product->name)

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">تعديل: {{ $product->name }}</h1>
    <a href="{{ route('accounting.products.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>

<form method="POST"
      action="{{ route('accounting.products.update', $product) }}"
      class="ac-prod-form-grid">
    @csrf
    @method('PUT')

    {{-- ── Main column ──────────────────────────────────────────────────── --}}
    <div class="ac-prod-form-main">

        {{-- المعلومات الأساسية --}}
        <div class="ac-card ac-card--compact">
            <div class="ac-card__body">
                <p class="ac-section-label">المعلومات الأساسية</p>

                <div class="ac-form-row">

                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="name">الاسم</label>
                        <input id="name" name="name" type="text"
                               class="ac-control {{ $errors->has('name') ? 'ac-control--error' : '' }}"
                               value="{{ old('name', $product->name) }}" required>
                        @error('name') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label" for="code">الرمز (SKU)</label>
                        <input id="code" name="code" type="text"
                               class="ac-control {{ $errors->has('code') ? 'ac-control--error' : '' }}"
                               value="{{ old('code', $product->code) }}" placeholder="اختياري">
                        @error('code') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label" for="barcode">الباركود</label>
                        <input id="barcode" name="barcode" type="text"
                               class="ac-control {{ $errors->has('barcode') ? 'ac-control--error' : '' }}"
                               value="{{ old('barcode', $product->barcode) }}" placeholder="كود POS / قارئ الصنف">
                        @error('barcode') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="type">النوع</label>
                        <select id="type" name="type"
                                class="ac-select {{ $errors->has('type') ? 'ac-select--error' : '' }}"
                                required>
                            <option value="service" {{ old('type', $product->type) === 'service' ? 'selected' : '' }}>خدمة</option>
                            <option value="product" {{ old('type', $product->type) === 'product' ? 'selected' : '' }}>منتج مادي</option>
                        </select>
                        @error('type') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label" for="unit">وحدة القياس</label>
                        <input id="unit" name="unit" type="text"
                               class="ac-control"
                               value="{{ old('unit', $product->unit) }}"
                               placeholder="قطعة / ساعة / كجم / متر …">
                    </div>

                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="description">الوصف</label>
                    <textarea id="description" name="description" rows="3" class="ac-control"
                              placeholder="وصف مختصر يظهر في الفاتورة...">{{ old('description', $product->description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- التسعير --}}
        <div class="ac-card ac-card--compact">
            <div class="ac-card__body">
                <p class="ac-section-label">التسعير</p>

                <div class="ac-form-row">

                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="sale_price">سعر البيع</label>
                        <input id="sale_price" name="sale_price" type="number"
                               step="0.01" min="0"
                               class="ac-control ac-control--num {{ $errors->has('sale_price') ? 'ac-control--error' : '' }}"
                               value="{{ old('sale_price', $product->sale_price) }}" required>
                        @error('sale_price') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label" for="purchase_price">سعر الشراء</label>
                        <input id="purchase_price" name="purchase_price" type="number"
                               step="0.01" min="0"
                               class="ac-control ac-control--num"
                               value="{{ old('purchase_price', $product->purchase_price) }}"
                               placeholder="اختياري">
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label" for="tax_rate">نسبة الضريبة %</label>
                        <input id="tax_rate" name="tax_rate" type="number"
                               step="0.01" min="0" max="100"
                               class="ac-control ac-control--num"
                               value="{{ old('tax_rate', $product->tax_rate) }}">
                    </div>

                    <div class="ac-form-group">
                        <label class="ac-label" for="account_id">حساب الإيراد الافتراضي</label>
                        <select id="account_id" name="account_id" class="ac-select">
                            <option value="">— اختياري —</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}"
                                    {{ old('account_id', $product->account_id) == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} — {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>
        </div>

    </div>

    {{-- ── Side column ──────────────────────────────────────────────────── --}}
    <div class="ac-prod-form-side">
        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">الإعدادات</p>

                <div class="ac-form-group">
                    <label class="ac-prod-toggle-label">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               id="is_active"
                               class="ac-prod-toggle-input"
                               {{ old('is_active', $product->is_active ? '1' : '0') == '1' ? 'checked' : '' }}>
                        <span class="ac-prod-toggle-track"></span>
                        <span>نشط في الكتالوج</span>
                    </label>
                    <p style="font-size:.78rem;color:var(--ac-text-muted);margin:.4rem 0 0 1.8rem">
                        العناصر غير النشطة لا تظهر في نافذة الاختيار.
                    </p>
                </div>

                {{-- Timestamps --}}
                <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--ac-border);font-size:.78rem;color:var(--ac-text-muted)">
                    <div>أُنشئ: {{ $product->created_at->format('Y-m-d') }}</div>
                    <div>آخر تعديل: {{ $product->updated_at->format('Y-m-d') }}</div>
                </div>

                <div style="margin-top:1.5rem;display:flex;flex-direction:column;gap:.6rem">
                    <button type="submit" class="ac-btn ac-btn--primary ac-btn--full">
                        حفظ التعديلات
                    </button>
                    <a href="{{ route('accounting.products.index') }}"
                       class="ac-btn ac-btn--secondary ac-btn--full">إلغاء</a>
                </div>
            </div>
        </div>
    </div>

</form>

@endsection

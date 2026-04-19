@extends('accounting._layout')

@section('title', 'نقطة البيع')

@section('content')
@php
    $taxEnabled = $settings->taxEnabled();
    $defaultTaxRate = $taxEnabled ? $settings->taxRate() : 0;
    $selectedSaleMode = old('sale_mode', 'paid');
    $selectedPaymentMethod = old('payment_method', 'cash');
@endphp

@include('accounting._flash')

@if($errors->has('pos'))
    <div class="ac-alert ac-alert--danger">{{ $errors->first('pos') }}</div>
@endif

<div class="ac-pos-page"
     data-default-tax-rate="{{ $defaultTaxRate }}"
     data-tax-enabled="{{ $taxEnabled ? '1' : '0' }}">

    <section class="ac-pos-hero">
        <div class="ac-pos-hero__copy">
            <span class="ac-pos-hero__eyebrow">بيع سريع</span>
            <h1 class="ac-pos-hero__title">نقطة بيع جاهزة للكاشير</h1>
            <p class="ac-pos-hero__text">اختيار أسرع، سلة أوضح، وإنهاء بيع فوري مع ربط تلقائي بالفاتورة والمخزون والحركة المحاسبية.</p>

            <div class="ac-pos-hero__meta">
                <span>رقم الفاتورة المتوقع: {{ $nextNumber }}</span>
                <span>العميل الافتراضي: {{ $walkInCustomer->name }}</span>
                @if($taxEnabled)
                    <span>{{ $settings->taxName() }} {{ number_format($defaultTaxRate, 2) }}%</span>
                @else
                    <span>الضريبة غير مفعلة</span>
                @endif
            </div>
        </div>

        <div class="ac-pos-stats">
            <article class="ac-pos-stat">
                <span class="ac-pos-stat__label">منتجات</span>
                <strong class="ac-pos-stat__value">{{ $stats['products'] }}</strong>
            </article>
            <article class="ac-pos-stat">
                <span class="ac-pos-stat__label">خدمات</span>
                <strong class="ac-pos-stat__value">{{ $stats['services'] }}</strong>
            </article>
            <article class="ac-pos-stat">
                <span class="ac-pos-stat__label">مخزون منخفض</span>
                <strong class="ac-pos-stat__value">{{ $stats['lowStock'] }}</strong>
            </article>
        </div>
    </section>

    <form method="POST" action="{{ route('accounting.pos.store') }}" id="pos-form" class="ac-pos-layout">
        @csrf

        <section class="ac-pos-catalog">
            <div class="ac-card ac-pos-panel">
                <div class="ac-card__header ac-pos-panel__header">
                    <div>
                        <h3 class="ac-card__title">المنتجات والخدمات</h3>
                        <p class="ac-pos-panel__subtitle">ابحث واضغط مرة واحدة لإضافة العنصر إلى السلة.</p>
                    </div>
                </div>

                <div class="ac-pos-toolbar">
                    <input type="text"
                           id="pos-barcode"
                           class="ac-control ac-pos-toolbar__barcode"
                           placeholder="باركود / قارئ صنف">

                    <input type="text"
                           id="pos-search"
                           class="ac-control"
                           placeholder="ابحث بالاسم أو الرمز...">

                    <div class="ac-pos-filter-tabs">
                        <button type="button" class="ac-pos-filter-tabs__btn is-active" data-type="">الكل</button>
                        <button type="button" class="ac-pos-filter-tabs__btn" data-type="product">منتجات</button>
                        <button type="button" class="ac-pos-filter-tabs__btn" data-type="service">خدمات</button>
                    </div>
                </div>

                <div class="ac-pos-products" id="pos-products">
                    @foreach($products as $product)
                        @php
                            $stock = (float) $product->quantity_on_hand;
                            $isOutOfStock = $product->type === 'product' && $stock <= 0;
                            $isLowStock = $product->type === 'product' && $stock > 0 && $stock <= 5;
                        @endphp
                        <button type="button"
                                class="ac-pos-product {{ $isOutOfStock ? 'ac-pos-product--disabled' : '' }}"
                                data-id="{{ $product->id }}"
                                data-name="{{ e($product->name) }}"
                                data-code="{{ e($product->code ?? '') }}"
                                data-barcode="{{ e($product->barcode ?? '') }}"
                                data-type="{{ $product->type }}"
                                data-price="{{ number_format((float) $product->sale_price, 2, '.', '') }}"
                                data-stock="{{ number_format($stock, 3, '.', '') }}"
                                data-unit="{{ e($product->unit ?? '') }}"
                                {{ $isOutOfStock ? 'disabled' : '' }}>
                            <div class="ac-pos-product__top">
                                <span class="ac-pos-product__type ac-pos-product__type--{{ $product->type }}">
                                    {{ $product->type === 'product' ? 'منتج' : 'خدمة' }}
                                </span>

                                @if($product->type === 'product')
                                    <span class="ac-pos-product__stock {{ $isOutOfStock ? 'is-out' : ($isLowStock ? 'is-low' : '') }}">
                                        {{ $isOutOfStock ? 'نفد' : 'متاح ' . rtrim(rtrim(number_format($stock, 3, '.', ''), '0'), '.') }}
                                    </span>
                                @endif
                            </div>

                            <div class="ac-pos-product__name">{{ $product->name }}</div>

                            <div class="ac-pos-product__meta">
                                <span>{{ $product->code ?: 'بدون رمز' }}</span>
                                <span>{{ $product->unit ?: ($product->type === 'product' ? 'قطعة' : 'خدمة') }}</span>
                            </div>

                            <div class="ac-pos-product__price">{{ number_format($product->sale_price, 2) }}</div>
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        <aside class="ac-pos-cart">
            <div class="ac-card ac-pos-panel ac-pos-panel--sticky">
                <div class="ac-card__header ac-pos-panel__header">
                    <div>
                        <h3 class="ac-card__title">السلة وإتمام البيع</h3>
                        <p class="ac-pos-panel__subtitle">راجع العناصر ثم اختر نوع العملية قبل الحفظ.</p>
                    </div>
                </div>

                <div class="ac-pos-cart__section">
                    <label class="ac-label" for="pos-customer">العميل</label>
                    <select id="pos-customer" name="customer_id" class="ac-select">
                        <option value="">{{ $walkInCustomer->name }}</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}@if($customer->phone) - {{ $customer->phone }}@endif
                            </option>
                        @endforeach
                    </select>
                    <p class="ac-pos-field-note">إذا تركته فارغًا سيتم الحفظ على {{ $walkInCustomer->name }}.</p>
                </div>

                <div class="ac-pos-cart__section">
                    <span class="ac-label">نوع العملية</span>
                    <div class="ac-pos-choice-grid">
                        <label class="ac-pos-choice {{ $selectedSaleMode === 'paid' ? 'is-selected' : '' }}">
                            <input type="radio" name="sale_mode" value="paid" {{ $selectedSaleMode === 'paid' ? 'checked' : '' }}>
                            <span>دفع فوري</span>
                        </label>
                        <label class="ac-pos-choice {{ $selectedSaleMode === 'pending' ? 'is-selected' : '' }}">
                            <input type="radio" name="sale_mode" value="pending" {{ $selectedSaleMode === 'pending' ? 'checked' : '' }}>
                            <span>آجل / على الحساب</span>
                        </label>
                    </div>
                </div>

                <div class="ac-pos-cart__section" id="pos-payment-methods">
                    <span class="ac-label">طريقة الدفع</span>
                    <div class="ac-pos-methods">
                        @foreach(['cash' => 'نقدي', 'card' => 'بطاقة', 'bank_transfer' => 'تحويل', 'wallet' => 'محفظة'] as $method => $label)
                            <label class="ac-pos-method {{ $selectedPaymentMethod === $method ? 'is-selected' : '' }}">
                                <input type="radio" name="payment_method" value="{{ $method }}" {{ $selectedPaymentMethod === $method ? 'checked' : '' }}>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('payment_method') <span class="ac-field-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-pos-cart__section ac-pos-dates">
                    <div class="ac-form-group">
                        <label class="ac-label" for="pos-issue-date">تاريخ البيع</label>
                        <input id="pos-issue-date"
                               type="date"
                               name="issue_date"
                               class="ac-control"
                               value="{{ old('issue_date', today()->toDateString()) }}"
                               required>
                    </div>

                    <div class="ac-form-group" id="pos-due-date-wrap">
                        <label class="ac-label" for="pos-due-date">تاريخ الاستحقاق</label>
                        <input id="pos-due-date"
                               type="date"
                               name="due_date"
                               class="ac-control"
                               value="{{ old('due_date', today()->toDateString()) }}">
                    </div>
                </div>

                <div class="ac-pos-cart__section">
                    <label class="ac-label" for="pos-notes">ملاحظات</label>
                    <textarea id="pos-notes" name="notes" rows="3" class="ac-control" placeholder="ملاحظة سريعة على البيع أو الطلب...">{{ old('notes') }}</textarea>
                </div>

                <div class="ac-pos-cart__section">
                    <div class="ac-pos-cart__head">
                        <span>العناصر المختارة</span>
                        <span id="pos-cart-count">0 عنصر</span>
                    </div>
                    <div class="ac-pos-cart__rows" id="pos-cart-rows">
                        <div class="ac-pos-cart__empty">أضف عناصر من القائمة لبدء عملية البيع.</div>
                    </div>
                </div>

                <div class="ac-pos-summary">
                    <div class="ac-pos-summary__row">
                        <span>المجموع</span>
                        <strong id="pos-gross-total">0.00</strong>
                    </div>
                    <div class="ac-pos-summary__row">
                        <span>الخصم</span>
                        <strong id="pos-discount">0.00</strong>
                    </div>
                    <div class="ac-pos-summary__row">
                        <span>Ø§Ù„Ù…Ø¬Ù…ÙˆØ¹</span>
                        <span class="ac-pos-summary__label">الصافي قبل الضريبة</span>
                        <strong id="pos-subtotal">0.00</strong>
                    </div>
                    <div class="ac-pos-summary__row">
                        <span>{{ $taxEnabled ? $settings->taxName() . ' (' . number_format($defaultTaxRate, 2) . '%)' : 'الضريبة' }}</span>
                        <strong id="pos-tax">0.00</strong>
                    </div>
                    <div class="ac-pos-summary__row ac-pos-summary__row--total">
                        <span>الإجمالي النهائي</span>
                        <strong id="pos-total">0.00</strong>
                    </div>
                </div>

                <div class="ac-pos-cart__section">
                    <span class="ac-label">خصم سريع</span>
                    <div class="ac-pos-discount-tools">
                        <div class="ac-pos-discount-buttons">
                            <button type="button" class="ac-pos-discount-btn" data-discount-pct="5">5%</button>
                            <button type="button" class="ac-pos-discount-btn" data-discount-pct="10">10%</button>
                            <button type="button" class="ac-pos-discount-btn" data-discount-pct="15">15%</button>
                            <button type="button" class="ac-pos-discount-btn" data-discount-pct="0">مسح</button>
                        </div>
                        <input type="number"
                               id="pos-discount-input"
                               name="discount_amount"
                               class="ac-control ac-control--num"
                               min="0"
                               step="0.01"
                               value="{{ old('discount_amount', 0) }}"
                               placeholder="0.00">
                    </div>
                    @error('discount_amount') <span class="ac-field-error">{{ $message }}</span> @enderror
                </div>

                <div id="pos-items-inputs"></div>

                <div class="ac-pos-submit">
                    <button type="submit" class="ac-btn ac-btn--primary ac-btn--full" id="pos-submit-btn" disabled>
                        إتمام البيع
                    </button>
                    <a href="{{ route('accounting.pos.drawer') }}"
                       target="_blank"
                       class="ac-btn ac-btn--secondary ac-btn--full">
                        فتح درج الكاشير
                    </a>
                    <a href="{{ route('accounting.invoices.index') }}" class="ac-btn ac-btn--secondary ac-btn--full">عرض الفواتير</a>
                </div>
            </div>
        </aside>
    </form>
</div>

<script type="application/json" id="pos-initial-items">@json(old('items', []))</script>
@endsection

@push('scripts')
    <script src="{{ asset('js/accounting-pos.js') }}" defer></script>
@endpush

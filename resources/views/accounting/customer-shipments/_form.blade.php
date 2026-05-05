@php
    $isEdit = isset($batch);
    $entriesSource = old('entries');

    if ($entriesSource === null) {
        $entriesSource = $isEdit
            ? $batch->entries->map(fn ($entry) => [
                'id' => $entry->id,
                'customer_id' => $entry->customer_id,
                'governorate_id' => $entry->governorate_id,
                'notes' => $entry->notes,
                'quantity' => (float) $entry->quantity,
                'shipment_type' => $entry->shipment_type,
                'unit_price' => (float) $entry->unit_price,
                'line_total' => (float) $entry->line_total,
                'entry_code' => $entry->entry_code,
            ])->values()->all()
            : [[
                'customer_id' => '',
                'governorate_id' => '',
                'notes' => '',
                'quantity' => '1',
                'shipment_type' => 'delivery',
                'unit_price' => '',
                'line_total' => '',
                'entry_code' => '',
            ]];
    }

    $customerOptions = $customers->map(fn ($customer) => [
        'id' => $customer->id,
        'label' => trim($customer->name . ($customer->phone ? ' - ' . $customer->phone : '')),
        'name' => $customer->name,
    ])->values();

    $governorateOptions = $governorates->map(fn ($gov) => [
        'id' => $gov->id,
        'label' => $gov->name_ar,
    ])->values();
@endphp

<form method="POST" action="{{ $formAction }}" id="shipment-form">
    @csrf
    @if(($formMethod ?? 'POST') !== 'POST')
        @method($formMethod)
    @endif

    <div class="ac-sh-layout">
        <div class="ac-sh-main">
            <div class="ac-card ac-card--compact ac-sh-hero">
                <div class="ac-card__body">
                    <div class="ac-sh-hero__head">
                        <div>
                            <p class="ac-section-label" style="margin:0">تسجيل يومي</p>
                            <h2 class="ac-sh-hero__title">أدخل شحنات العملاء ثم راجعها في نفس اليوم</h2>
                        </div>
                        <span class="ac-sh-hero__badge">{{ $isEdit ? 'تعديل' : 'جديد' }}</span>
                    </div>
                    <div class="ac-sh-hero__steps">
                        <div class="ac-sh-hero__step"><span>1</span> اختر التاريخ</div>
                        <div class="ac-sh-hero__step"><span>2</span> أضف سطور العملاء والمحافظات</div>
                        <div class="ac-sh-hero__step"><span>3</span> راجع الإجماليات والأكواد</div>
                    </div>
                </div>
            </div>

            <div class="ac-card ac-card--compact">
                <div class="ac-card__body">
                    <p class="ac-section-label">بيانات اليوم</p>
                    <div class="ac-sh-top-grid">
                        <div class="ac-form-group">
                            <label class="ac-label ac-label--required" for="shipment_date">تاريخ الشحنات</label>
                            <input type="date" id="shipment_date" name="shipment_date"
                                   class="ac-control {{ $errors->has('shipment_date') ? 'ac-control--error' : '' }}"
                                   value="{{ old('shipment_date', $isEdit ? $batch->shipment_date->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                   required>
                            @error('shipment_date') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ac-form-group">
                            <label class="ac-label" for="batch_notes">ملاحظات عامة</label>
                            <input type="text" id="batch_notes" name="batch_notes" class="ac-control"
                                   value="{{ old('batch_notes', $isEdit ? $batch->notes : '') }}"
                                   placeholder="اختياري: ملاحظات على شحنات اليوم بالكامل">
                        </div>
                    </div>
                </div>
            </div>

            <div class="ac-card ac-card--compact">
                <div class="ac-card__body">
                    <div class="ac-sh-table-head">
                        <div>
                            <p class="ac-section-label" style="margin:0">سطر الشحنات</p>
                            <p class="ac-pl-create-muted">ابحث عن العميل والمحافظة، وسيتم جلب سعر الشوال تلقائيًا من قائمة تسعيره أو من القائمة الأساسية.</p>
                        </div>
                        <button type="button" class="ac-btn ac-btn--primary ac-btn--sm" id="add-shipment-row">+ إضافة عميل</button>
                    </div>

                    @if($errors->has('entries'))
                        <div class="ac-alert ac-alert--error">{{ $errors->first('entries') }}</div>
                    @endif

                    <div class="ac-sh-table-wrap">
                        <table class="ac-sh-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>العميل</th>
                                    <th>المحافظة</th>
                                    <th>ملاحظات</th>
                                    <th>عدد الأشولة</th>
                                    <th>نوع الشحنة</th>
                                    <th>سعر الشوال</th>
                                    <th>الإجمالي</th>
                                    <th>الكود</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="shipment-rows"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="ac-sh-side">
            <div class="ac-card">
                <div class="ac-card__body">
                    <p class="ac-section-label">ملخص سريع</p>
                    <div class="ac-sh-stats">
                        <div class="ac-sh-stat">
                            <span class="ac-sh-stat__label">عدد السطور</span>
                            <span class="ac-sh-stat__value" id="summary-lines">0</span>
                        </div>
                        <div class="ac-sh-stat">
                            <span class="ac-sh-stat__label">إجمالي الأشولة</span>
                            <span class="ac-sh-stat__value" id="summary-quantity">0.00</span>
                        </div>
                        <div class="ac-sh-stat">
                            <span class="ac-sh-stat__label">إجمالي القيمة</span>
                            <span class="ac-sh-stat__value" id="summary-sales">0.00</span>
                        </div>
                    </div>

                    <div class="ac-sh-readiness">
                        <p class="ac-sh-readiness__title">جاهزية الحفظ</p>
                        <div class="ac-sh-readiness__item is-pending" data-ready-check="date">
                            <span class="ac-sh-readiness__dot"></span>
                            <span>اختيار تاريخ للشحنات</span>
                        </div>
                        <div class="ac-sh-readiness__item is-pending" data-ready-check="rows">
                            <span class="ac-sh-readiness__dot"></span>
                            <span>إضافة سطر واحد على الأقل</span>
                        </div>
                        <div class="ac-sh-readiness__item is-pending" data-ready-check="validRows">
                            <span class="ac-sh-readiness__dot"></span>
                            <span>إكمال العميل والمحافظة والسعر والكمية</span>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:.6rem;margin-top:1.25rem">
                        <button type="submit" class="ac-btn ac-btn--primary ac-btn--full">{{ $submitLabel }}</button>
                        <a href="{{ route('accounting.customer-shipments.index') }}" class="ac-btn ac-btn--secondary ac-btn--full">إلغاء</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<datalist id="shipment-customers-list">
    @foreach($customerOptions as $option)
        <option value="{{ $option['label'] }}"></option>
    @endforeach
</datalist>

<datalist id="shipment-governorates-list">
    @foreach($governorateOptions as $option)
        <option value="{{ $option['label'] }}"></option>
    @endforeach
</datalist>

<template id="shipment-row-template">
    <tr class="ac-sh-row" data-index="__INDEX__">
        <td class="ac-sh-row__num">__NUM__</td>
        <td>
            <input type="hidden" name="entries[__INDEX__][id]" class="js-entry-id" value="__ID__">
            <input type="hidden" name="entries[__INDEX__][customer_id]" class="js-customer-id" value="__CUSTOMER_ID__">
            <input type="text" class="ac-control ac-control--sm js-customer-search __CUSTOMER_ERROR__"
                   list="shipment-customers-list" value="__CUSTOMER_LABEL__" placeholder="ابحث عن عميل">
            <span class="ac-field-error js-customer-error">__CUSTOMER_ERROR_TEXT__</span>
        </td>
        <td>
            <input type="hidden" name="entries[__INDEX__][governorate_id]" class="js-governorate-id" value="__GOVERNORATE_ID__">
            <input type="text" class="ac-control ac-control--sm js-governorate-search __GOVERNORATE_ERROR__"
                   list="shipment-governorates-list" value="__GOVERNORATE_LABEL__" placeholder="ابحث عن محافظة">
            <span class="ac-field-error js-governorate-error">__GOVERNORATE_ERROR_TEXT__</span>
        </td>
        <td>
            <input type="text" name="entries[__INDEX__][notes]" class="ac-control ac-control--sm"
                   value="__NOTES__" placeholder="اختياري">
        </td>
        <td>
            <input type="number" name="entries[__INDEX__][quantity]" class="ac-control ac-control--sm js-quantity __QUANTITY_ERROR__"
                   value="__QUANTITY__" min="0.25" step="0.25" placeholder="0.25">
            <span class="ac-field-error js-quantity-error">__QUANTITY_ERROR_TEXT__</span>
        </td>
        <td>
            <select name="entries[__INDEX__][shipment_type]" class="ac-select ac-select--sm js-shipment-type">
                <option value="delivery" __DELIVERY_SELECTED__>تسليم</option>
                <option value="return" __RETURN_SELECTED__>مرتجع</option>
            </select>
        </td>
        <td>
            <input type="text" class="ac-control ac-control--sm js-unit-price" value="__UNIT_PRICE__" readonly>
            <div class="ac-sh-source js-price-source">__PRICE_SOURCE__</div>
        </td>
        <td>
            <input type="text" class="ac-control ac-control--sm js-line-total" value="__LINE_TOTAL__" readonly>
        </td>
        <td>
            <span class="ac-sh-code">__ENTRY_CODE__</span>
        </td>
        <td>
            <button type="button" class="ac-btn ac-btn--ghost ac-btn--xs js-remove-row">حذف</button>
        </td>
    </tr>
</template>

@push('scripts')
<script>
(function () {
    const customerOptions = @json($customerOptions, JSON_UNESCAPED_UNICODE);
    const governorateOptions = @json($governorateOptions, JSON_UNESCAPED_UNICODE);
    const initialRows = @json($entriesSource, JSON_UNESCAPED_UNICODE);
    const resolvePriceUrl = @json(route('accounting.customer-shipments.resolve-price'));
    const errors = @json($errors->toArray(), JSON_UNESCAPED_UNICODE);
    const rowsContainer = document.getElementById('shipment-rows');
    const rowTemplate = document.getElementById('shipment-row-template').innerHTML;
    const addRowBtn = document.getElementById('add-shipment-row');
    const shipmentDateInput = document.getElementById('shipment_date');
    let rowIndex = 0;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function findCustomerLabel(id) {
        return customerOptions.find(option => String(option.id) === String(id))?.label || '';
    }

    function findGovernorateLabel(id) {
        return governorateOptions.find(option => String(option.id) === String(id))?.label || '';
    }

    function errorText(path) {
        return errors[path]?.[0] || '';
    }

    function errorClass(path) {
        return errors[path] ? 'ac-control--error' : '';
    }

    function addRow(data = {}) {
        const index = rowIndex++;
        let html = rowTemplate
            .replace(/__INDEX__/g, index)
            .replace(/__NUM__/g, rowsContainer.children.length + 1)
            .replace(/__ID__/g, escapeHtml(data.id || ''))
            .replace(/__CUSTOMER_ID__/g, escapeHtml(data.customer_id || ''))
            .replace(/__CUSTOMER_LABEL__/g, escapeHtml(findCustomerLabel(data.customer_id) || data.customer_label || ''))
            .replace(/__GOVERNORATE_ID__/g, escapeHtml(data.governorate_id || ''))
            .replace(/__GOVERNORATE_LABEL__/g, escapeHtml(findGovernorateLabel(data.governorate_id) || data.governorate_label || ''))
            .replace(/__NOTES__/g, escapeHtml(data.notes || ''))
            .replace(/__QUANTITY__/g, escapeHtml(data.quantity || '1'))
            .replace(/__UNIT_PRICE__/g, escapeHtml(data.unit_price || ''))
            .replace(/__LINE_TOTAL__/g, escapeHtml(data.line_total || ''))
            .replace(/__ENTRY_CODE__/g, escapeHtml(data.entry_code || 'سيولد بعد الحفظ'))
            .replace(/__PRICE_SOURCE__/g, escapeHtml(data.price_source_label || 'سيتم تحديده تلقائيًا'))
            .replace(/__CUSTOMER_ERROR__/g, errorClass('entries.' + index + '.customer_id'))
            .replace(/__GOVERNORATE_ERROR__/g, errorClass('entries.' + index + '.governorate_id'))
            .replace(/__QUANTITY_ERROR__/g, errorClass('entries.' + index + '.quantity'))
            .replace(/__CUSTOMER_ERROR_TEXT__/g, escapeHtml(errorText('entries.' + index + '.customer_id')))
            .replace(/__GOVERNORATE_ERROR_TEXT__/g, escapeHtml(errorText('entries.' + index + '.governorate_id')))
            .replace(/__QUANTITY_ERROR_TEXT__/g, escapeHtml(errorText('entries.' + index + '.quantity')))
            .replace(/__DELIVERY_SELECTED__/g, (data.shipment_type || 'delivery') === 'delivery' ? 'selected' : '')
            .replace(/__RETURN_SELECTED__/g, (data.shipment_type || 'delivery') === 'return' ? 'selected' : '');

        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = html.trim();
        const row = wrapper.firstElementChild;
        rowsContainer.appendChild(row);
        bindRow(row);
        updateSummary();
    }

    function syncSearch(input, options, hiddenInput) {
        const normalized = input.value.trim();
        const option = options.find(item => item.label === normalized);
        hiddenInput.value = option ? option.id : '';
        return option;
    }

    function recalcLine(row) {
        const quantity = parseFloat(row.querySelector('.js-quantity').value || '0') || 0;
        const unitPrice = parseFloat(row.querySelector('.js-unit-price').value || '0') || 0;
        row.querySelector('.js-line-total').value = (quantity * unitPrice).toFixed(2);
        updateSummary();
    }

    async function resolveRowPrice(row) {
        const customerId = row.querySelector('.js-customer-id').value;
        const governorateId = row.querySelector('.js-governorate-id').value;
        const shipmentType = row.querySelector('.js-shipment-type').value;
        const unitPriceInput = row.querySelector('.js-unit-price');
        const sourceNode = row.querySelector('.js-price-source');

        if (!customerId || !governorateId) {
            unitPriceInput.value = '';
            sourceNode.textContent = 'حدد العميل والمحافظة أولًا';
            recalcLine(row);
            return;
        }

        sourceNode.textContent = 'جارٍ جلب السعر...';

        try {
            const url = new URL(resolvePriceUrl, window.location.origin);
            url.searchParams.set('customer_id', customerId);
            url.searchParams.set('governorate_id', governorateId);
            url.searchParams.set('shipment_type', shipmentType);

            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('price');
            }

            const data = await response.json();
            unitPriceInput.value = data.unit_price;
            sourceNode.textContent = data.price_list_name
                ? 'من قائمة: ' + data.price_list_name
                : 'تم تحديد السعر';
            recalcLine(row);
        } catch (error) {
            unitPriceInput.value = '';
            sourceNode.textContent = 'لا يوجد سعر متاح لهذا السطر';
            recalcLine(row);
        }
    }

    function bindRow(row) {
        const customerSearch = row.querySelector('.js-customer-search');
        const customerId = row.querySelector('.js-customer-id');
        const governorateSearch = row.querySelector('.js-governorate-search');
        const governorateId = row.querySelector('.js-governorate-id');
        const quantityInput = row.querySelector('.js-quantity');
        const shipmentType = row.querySelector('.js-shipment-type');

        customerSearch.addEventListener('input', () => {
            syncSearch(customerSearch, customerOptions, customerId);
            resolveRowPrice(row);
        });

        governorateSearch.addEventListener('input', () => {
            syncSearch(governorateSearch, governorateOptions, governorateId);
            resolveRowPrice(row);
        });

        shipmentType.addEventListener('change', () => resolveRowPrice(row));
        quantityInput.addEventListener('input', () => recalcLine(row));

        row.querySelector('.js-remove-row').addEventListener('click', () => {
            row.remove();
            renumberRows();
            updateSummary();
        });

        if (customerId.value && governorateId.value) {
            resolveRowPrice(row);
        } else {
            recalcLine(row);
        }
    }

    function renumberRows() {
        Array.from(rowsContainer.children).forEach((row, idx) => {
            row.querySelector('.ac-sh-row__num').textContent = idx + 1;
        });
    }

    function setChecklistState(key, isReady) {
        const item = document.querySelector('[data-ready-check="' + key + '"]');
        if (!item) return;
        item.classList.toggle('is-ready', isReady);
        item.classList.toggle('is-pending', !isReady);
    }

    function updateSummary() {
        const rows = Array.from(rowsContainer.querySelectorAll('.ac-sh-row'));
        const totalQuantity = rows.reduce((sum, row) => sum + (parseFloat(row.querySelector('.js-quantity').value || '0') || 0), 0);
        const totalSales = rows.reduce((sum, row) => sum + (parseFloat(row.querySelector('.js-line-total').value || '0') || 0), 0);
        const validRows = rows.length > 0 && rows.every(row => {
            return row.querySelector('.js-customer-id').value
                && row.querySelector('.js-governorate-id').value
                && row.querySelector('.js-quantity').value
                && row.querySelector('.js-unit-price').value;
        });

        document.getElementById('summary-lines').textContent = rows.length;
        document.getElementById('summary-quantity').textContent = totalQuantity.toFixed(2);
        document.getElementById('summary-sales').textContent = totalSales.toFixed(2);

        setChecklistState('date', !!shipmentDateInput.value);
        setChecklistState('rows', rows.length > 0);
        setChecklistState('validRows', validRows);
    }

    addRowBtn.addEventListener('click', () => addRow({ shipment_type: 'delivery', quantity: '1' }));
    shipmentDateInput.addEventListener('change', updateSummary);

    initialRows.forEach(row => addRow(row));
    renumberRows();
})();
</script>
@endpush

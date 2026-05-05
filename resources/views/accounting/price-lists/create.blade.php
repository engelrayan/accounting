@extends('accounting._layout')

@section('title', 'قائمة أسعار جديدة')

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">إنشاء قائمة أسعار جديدة</h1>
    <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap">
        @if(companyModuleEnabled('customer_shipments'))
        <a href="{{ route('accounting.customer-shipments.index') }}" class="ac-btn ac-btn--ghost">شحنات العملاء</a>
        @endif
        <a href="{{ route('accounting.price-lists.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
    </div>
</div>

<form method="POST" action="{{ route('accounting.price-lists.store') }}" id="pl-form">
    @csrf

    <div class="ac-pl-layout">
        <div class="ac-pl-main">
            <div class="ac-card ac-card--compact ac-pl-create-hero">
                <div class="ac-card__body">
                    <div class="ac-pl-create-hero__head">
                        <div>
                            <p class="ac-section-label" style="margin:0">خطوات سريعة</p>
                            <h2 class="ac-pl-create-hero__title">أنشئ قائمة الأسعار في 3 خطوات</h2>
                        </div>
                        <span class="ac-pl-create-hero__badge">جديد</span>
                    </div>
                    <div class="ac-pl-create-hero__steps">
                        <div class="ac-pl-create-hero__step"><span>1</span> اسم القائمة وحالتها</div>
                        <div class="ac-pl-create-hero__step"><span>2</span> اختيار المحافظات وإدخال السعر</div>
                        <div class="ac-pl-create-hero__step"><span>3</span> ربط العملاء ثم الحفظ</div>
                    </div>
                </div>
            </div>

            <div class="ac-pl-create-summary">
                <div class="ac-pl-create-summary__item">
                    <span class="ac-pl-create-summary__label">اسم القائمة</span>
                    <strong class="ac-pl-create-summary__value" id="summary-name">لم يتم إدخال اسم بعد</strong>
                </div>
                <div class="ac-pl-create-summary__item">
                    <span class="ac-pl-create-summary__label">محافظات مسعرة</span>
                    <strong class="ac-pl-create-summary__value" id="summary-priced-count">0</strong>
                </div>
                <div class="ac-pl-create-summary__item">
                    <span class="ac-pl-create-summary__label">بسعر مرتجع</span>
                    <strong class="ac-pl-create-summary__value" id="summary-return-count">0</strong>
                </div>
                <div class="ac-pl-create-summary__item">
                    <span class="ac-pl-create-summary__label">الحالة</span>
                    <strong class="ac-pl-create-summary__value" id="summary-status">نشطة</strong>
                </div>
            </div>

            <div class="ac-card ac-card--compact">
                <div class="ac-card__body">
                    <p class="ac-section-label">معلومات القائمة</p>
                    <div class="ac-pl-info-row">
                        <div class="ac-form-group" style="flex:1">
                            <label class="ac-label ac-label--required" for="name">اسم القائمة</label>
                            <input id="name" name="name" type="text"
                                   class="ac-control {{ $errors->has('name') ? 'ac-control--error' : '' }}"
                                   value="{{ old('name') }}"
                                   placeholder="مثال: تعريفة القاهرة الكبرى..."
                                   required>
                            <p class="ac-pl-create-muted">اسم واضح يساعد الفريق يميز هذه القائمة بسرعة عند الربط مع العملاء.</p>
                            @error('name') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ac-form-group" style="flex:1">
                            <label class="ac-label" for="description">ملاحظات</label>
                            <input id="description" name="description" type="text"
                                   class="ac-control"
                                   placeholder="أي تفاصيل إضافية..."
                                   value="{{ old('description') }}">
                            <p class="ac-pl-create-muted">اختياري: مثل نطاق التغطية أو ملاحظات التشغيل لفريق المبيعات أو الشحن.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ac-card ac-card--compact">
                <div class="ac-card__body">
                    <p class="ac-section-label">ربط العملاء بالقائمة</p>
                    <p style="font-size:.82rem;color:var(--ac-text-muted);margin:.25rem 0 .85rem">
                        اختر العملاء الذين سيُطبّق عليهم هذا التسعير تلقائياً.
                    </p>
                    <div class="ac-pl-cust-head">
                        <span class="ac-pl-create-counter" id="customer-count-badge">0 عملاء محددون</span>
                    </div>

                    <div class="ac-pl-cust-box" id="cust-box">
                        <div class="ac-pl-cust-chips" id="cust-chips">
                            <span class="ac-pl-cust-placeholder" id="cust-placeholder">لا يوجد عملاء محددون</span>
                        </div>
                        <div class="ac-pl-cust-search-wrap">
                            <svg class="ac-pl-cust-search-icon" width="14" height="14" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            <input type="text" id="cust-search" class="ac-pl-cust-search"
                                   placeholder="ابحث عن عميل...">
                        </div>
                        <div class="ac-pl-cust-dropdown" id="cust-dropdown" style="display:none">
                            @foreach($customers as $cust)
                            <div class="ac-pl-cust-opt" data-id="{{ $cust->id }}" data-name="{{ $cust->name }}">
                                {{ $cust->name }}
                            </div>
                            @endforeach
                            <div class="ac-pl-cust-empty" id="cust-empty" style="display:none">لا توجد نتائج</div>
                        </div>
                    </div>

                    <div id="cust-inputs"></div>
                </div>
            </div>

            <div class="ac-card ac-card--compact">
                <div class="ac-card__body">
                    <div class="ac-pl-tbl-header">
                        <div>
                            <p class="ac-section-label" style="margin:0">المحافظات والأسعار</p>
                            <p class="ac-pl-create-muted">فعّل المحافظة ثم أدخل سعر التسليم الإجباري، ويمكنك إضافة سعر مرتجع عند الحاجة.</p>
                        </div>
                        <div class="ac-pl-tbl-header__actions">
                            <button type="button" class="ac-btn ac-btn--ghost ac-btn--xs" id="select-all-btn">تحديد الكل</button>
                            <button type="button" class="ac-btn ac-btn--ghost ac-btn--xs" id="clear-all-btn">إلغاء الكل</button>
                            <a href="{{ route('accounting.governorates.index') }}"
                               class="ac-btn ac-btn--ghost ac-btn--xs" target="_blank">+ إضافة محافظة</a>
                        </div>
                    </div>

                    <div class="ac-pl-create-tools">
                        <div class="ac-pl-create-search-wrap">
                            <input type="text" id="gov-search" class="ac-control ac-pl-create-search" placeholder="ابحث عن محافظة...">
                        </div>
                        <div class="ac-pl-create-tools__meta">
                            <label class="ac-pl-create-check">
                                <input type="checkbox" id="selected-only-toggle">
                                <span>عرض المحدد فقط</span>
                            </label>
                            <span class="ac-pl-create-counter" id="visible-count">0 محافظة ظاهرة</span>
                        </div>
                    </div>

                    <div class="ac-alert ac-alert--warning ac-pl-inline-alert" id="pl-validation-alert" style="display:none"></div>

                    <div class="ac-pl-bulk-bar" id="bulk-bar" style="display:none">
                        <span class="ac-pl-bulk-bar__count" id="bulk-count">0 محافظة</span>
                        <div class="ac-pl-bulk-bar__fields">
                            <div class="ac-pl-bulk-bar__field">
                                <label class="ac-pl-bulk-bar__lbl">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12l5 5L19 7"/></svg>
                                    سعر تسليم موحد
                                </label>
                                <input type="number" id="bulk-price-del"
                                       class="ac-control ac-control--num ac-pl-bulk-inp"
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="ac-pl-bulk-bar__field">
                                <label class="ac-pl-bulk-bar__lbl">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-5H1"/></svg>
                                    سعر مرتجع موحد
                                </label>
                                <input type="number" id="bulk-price-ret"
                                       class="ac-control ac-control--num ac-pl-bulk-inp"
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                            <button type="button" class="ac-btn ac-btn--primary ac-btn--sm" id="apply-bulk-btn">
                                تطبيق
                            </button>
                        </div>
                        <span class="ac-pl-bulk-bar__hint" id="bulk-hint">يمكنك تطبيق سعر موحد على كل المحافظات المحددة.</span>
                    </div>

                    <div class="ac-pl-tbl-wrap">
                        <table class="ac-pl-tbl">
                            <colgroup>
                                <col style="width:42px">
                                <col>
                                <col style="width:300px">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="ac-pl-tbl__check-col"></th>
                                    <th>المحافظة</th>
                                    <th>
                                        <div class="ac-pl-prices-hdr">
                                            <span>
                                                <span class="ac-pl-th-badge ac-pl-th-badge--delivery">تسليم</span>
                                                <span class="ac-pl-th-required">*</span>
                                            </span>
                                            <span>
                                                <span class="ac-pl-th-badge ac-pl-th-badge--return">مرتجع</span>
                                                <span class="ac-pl-th-optional">اختياري</span>
                                            </span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="gov-tbody">
                                @foreach($governorates as $gov)
                                <tr class="ac-pl-row" id="gov-row-{{ $gov->id }}" data-gov-id="{{ $gov->id }}" data-gov-name="{{ mb_strtolower($gov->name_ar) }}">
                                    <td class="ac-pl-tbl__check-col">
                                        <input type="checkbox"
                                               name="govs[{{ $gov->id }}][enabled]"
                                               value="1"
                                               class="ac-pl-gov-checkbox ac-checkbox"
                                               data-gov-id="{{ $gov->id }}"
                                               {{ old("govs.{$gov->id}.enabled") ? 'checked' : '' }}>
                                    </td>
                                    <td class="ac-pl-tbl__name">{{ $gov->name_ar }}</td>
                                    <td class="ac-pl-tbl__prices-col">
                                        <div class="ac-pl-two-prices">
                                            <input type="number"
                                                   name="govs[{{ $gov->id }}][price]"
                                                   class="ac-control ac-control--num ac-pl-delivery-inp"
                                                   step="0.01" min="0"
                                                   placeholder="0.00"
                                                   data-gov-id="{{ $gov->id }}"
                                                   value="{{ old("govs.{$gov->id}.price") }}"
                                                   {{ !old("govs.{$gov->id}.enabled") ? 'disabled' : '' }}>
                                            <span class="ac-pl-two-prices__sep"></span>
                                            <input type="number"
                                                   name="govs[{{ $gov->id }}][return_price]"
                                                   class="ac-control ac-control--num ac-pl-return-inp"
                                                   step="0.01" min="0"
                                                   placeholder="—"
                                                   data-gov-id="{{ $gov->id }}"
                                                   value="{{ old("govs.{$gov->id}.return_price") }}"
                                                   {{ !old("govs.{$gov->id}.enabled") ? 'disabled' : '' }}>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="ac-pl-create-muted" id="gov-empty" style="display:none;margin-top:.6rem">لا توجد محافظات مطابقة للبحث.</p>
                </div>
            </div>
        </div>

        <div class="ac-pl-side">
            <div class="ac-card">
                <div class="ac-card__body">
                    <p class="ac-section-label">الإعدادات</p>

                    <div class="ac-form-group">
                        <label class="ac-prod-toggle-label">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   id="is_active" class="ac-prod-toggle-input"
                                   {{ old('is_active', '1') ? 'checked' : '' }}>
                            <span class="ac-prod-toggle-track"></span>
                            <span>قائمة نشطة</span>
                        </label>
                    </div>

                    <div class="ac-pl-side-stats">
                        <div class="ac-pl-side-stat">
                            <span class="ac-pl-side-stat__label">محافظات محددة</span>
                            <span class="ac-pl-side-stat__val" id="stat-gov-count">0</span>
                        </div>
                        <div class="ac-pl-side-stat">
                            <span class="ac-pl-side-stat__label">محافظات مسعرة بالكامل</span>
                            <span class="ac-pl-side-stat__val" id="stat-priced-count">0</span>
                        </div>
                        <div class="ac-pl-side-stat">
                            <span class="ac-pl-side-stat__label">بسعر مرتجع</span>
                            <span class="ac-pl-side-stat__val" id="stat-return-count">0</span>
                        </div>
                        <div class="ac-pl-side-stat">
                            <span class="ac-pl-side-stat__label">عملاء مرتبطون</span>
                            <span class="ac-pl-side-stat__val" id="stat-cust-count">0</span>
                        </div>
                    </div>

                    <div class="ac-pl-readiness" id="pl-readiness">
                        <p class="ac-pl-readiness__title">جاهزية الحفظ</p>
                        <div class="ac-pl-readiness__item is-pending" data-ready-check="name">
                            <span class="ac-pl-readiness__dot"></span>
                            <span>إدخال اسم للقائمة</span>
                        </div>
                        <div class="ac-pl-readiness__item is-pending" data-ready-check="govs">
                            <span class="ac-pl-readiness__dot"></span>
                            <span>اختيار محافظة واحدة على الأقل</span>
                        </div>
                        <div class="ac-pl-readiness__item is-pending" data-ready-check="prices">
                            <span class="ac-pl-readiness__dot"></span>
                            <span>إدخال سعر تسليم لكل محافظة محددة</span>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:.6rem;margin-top:1.25rem">
                        <button type="submit" class="ac-btn ac-btn--primary ac-btn--full">
                            حفظ القائمة
                        </button>
                        <a href="{{ route('accounting.price-lists.index') }}"
                           class="ac-btn ac-btn--secondary ac-btn--full">إلغاء</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('pl-form');
    const nameInput = document.getElementById('name');
    const isActiveInput = document.getElementById('is_active');

    const checkboxes = Array.from(document.querySelectorAll('.ac-pl-gov-checkbox'));
    const govRows = Array.from(document.querySelectorAll('.ac-pl-row'));
    const bulkBar = document.getElementById('bulk-bar');
    const bulkCount = document.getElementById('bulk-count');
    const bulkDelInp = document.getElementById('bulk-price-del');
    const bulkRetInp = document.getElementById('bulk-price-ret');
    const applyBtn = document.getElementById('apply-bulk-btn');
    const selectAll = document.getElementById('select-all-btn');
    const clearAll = document.getElementById('clear-all-btn');
    const statGov = document.getElementById('stat-gov-count');
    const statPriced = document.getElementById('stat-priced-count');
    const statReturn = document.getElementById('stat-return-count');
    const govSearch = document.getElementById('gov-search');
    const selectedOnlyToggle = document.getElementById('selected-only-toggle');
    const visibleCount = document.getElementById('visible-count');
    const govEmpty = document.getElementById('gov-empty');
    const validationAlert = document.getElementById('pl-validation-alert');
    const bulkHint = document.getElementById('bulk-hint');
    const summaryName = document.getElementById('summary-name');
    const summaryPricedCount = document.getElementById('summary-priced-count');
    const summaryReturnCount = document.getElementById('summary-return-count');
    const summaryStatus = document.getElementById('summary-status');

    const custSearch = document.getElementById('cust-search');
    const custDropdown = document.getElementById('cust-dropdown');
    const custChips = document.getElementById('cust-chips');
    const custInputs = document.getElementById('cust-inputs');
    const custPlaceholder = document.getElementById('cust-placeholder');
    const custEmpty = document.getElementById('cust-empty');
    const statCust = document.getElementById('stat-cust-count');
    const customerCountBadge = document.getElementById('customer-count-badge');
    const allOpts = Array.from(document.querySelectorAll('.ac-pl-cust-opt'));
    const preSelected = @json(array_map('strval', old('customer_ids', [])));
    let selected = {};

    allOpts.forEach(opt => {
        if (preSelected.includes(opt.dataset.id)) {
            selected[opt.dataset.id] = opt.dataset.name;
        }
    });

    function getSelectedRows() {
        return govRows.filter(row => row.querySelector('.ac-pl-gov-checkbox')?.checked);
    }

    function getSelectedRowsWithPrice() {
        return getSelectedRows().filter(row => {
            const input = row.querySelector('.ac-pl-delivery-inp');
            return input && input.value.trim() !== '';
        });
    }

    function getSelectedRowsWithReturnPrice() {
        return getSelectedRows().filter(row => {
            const input = row.querySelector('.ac-pl-return-inp');
            return input && input.value.trim() !== '';
        });
    }

    function setChecklistState(key, isReady) {
        const item = document.querySelector('[data-ready-check="' + key + '"]');
        if (!item) return;
        item.classList.toggle('is-ready', isReady);
        item.classList.toggle('is-pending', !isReady);
    }

    function updateReadiness() {
        const selectedRows = getSelectedRows();
        const pricedRows = getSelectedRowsWithPrice();
        setChecklistState('name', !!nameInput?.value.trim());
        setChecklistState('govs', selectedRows.length > 0);
        setChecklistState('prices', selectedRows.length > 0 && pricedRows.length === selectedRows.length);
    }

    function updateSummaryName() {
        const value = nameInput?.value.trim() || '';
        if (summaryName) {
            summaryName.textContent = value || 'لم يتم إدخال اسم بعد';
        }
    }

    function updateStatusSummary() {
        if (summaryStatus) {
            summaryStatus.textContent = isActiveInput?.checked ? 'نشطة' : 'غير نشطة';
        }
    }

    function updateStats() {
        const selectedRows = getSelectedRows();
        const pricedRows = getSelectedRowsWithPrice();
        const returnRows = getSelectedRowsWithReturnPrice();

        if (statGov) statGov.textContent = selectedRows.length;
        if (statPriced) statPriced.textContent = pricedRows.length;
        if (statReturn) statReturn.textContent = returnRows.length;
        if (summaryPricedCount) summaryPricedCount.textContent = pricedRows.length;
        if (summaryReturnCount) summaryReturnCount.textContent = returnRows.length;

        if (selectedRows.length > 0) {
            bulkBar.style.display = 'flex';
            bulkCount.textContent = selectedRows.length + ' ' + (selectedRows.length === 1 ? 'محافظة' : 'محافظات');
            if (bulkHint) {
                bulkHint.textContent = 'سيتم تطبيق السعر الموحد على ' + selectedRows.length + ' ' + (selectedRows.length === 1 ? 'محافظة محددة' : 'محافظات محددة');
            }
        } else {
            bulkBar.style.display = 'none';
        }

        updateReadiness();
    }

    function updateGovFilter() {
        const q = (govSearch?.value || '').trim().toLowerCase();
        const selectedOnly = !!selectedOnlyToggle?.checked;
        let visible = 0;

        govRows.forEach(row => {
            const isSelected = row.querySelector('.ac-pl-gov-checkbox')?.checked;
            const matchesSearch = !q || (row.dataset.govName || '').includes(q);
            const match = matchesSearch && (!selectedOnly || isSelected);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        if (visibleCount) {
            visibleCount.textContent = visible + ' ' + (visible === 1 ? 'محافظة ظاهرة' : 'محافظات ظاهرة');
        }

        if (govEmpty) {
            govEmpty.style.display = visible === 0 ? '' : 'none';
        }
    }

    function setRow(cb, checked) {
        const row = document.getElementById('gov-row-' + cb.dataset.govId);
        const delInp = row?.querySelector('.ac-pl-delivery-inp');
        const retInp = row?.querySelector('.ac-pl-return-inp');

        if (delInp) delInp.disabled = !checked;
        if (retInp) retInp.disabled = !checked;

        row?.classList.toggle('ac-pl-row--active', checked);
        if (!checked) {
            row?.classList.remove('ac-pl-row--missing');
        }
    }

    function clearValidationState() {
        if (!validationAlert) return;
        validationAlert.style.display = 'none';
        validationAlert.textContent = '';
        govRows.forEach(row => row.classList.remove('ac-pl-row--missing'));
    }

    function showValidation(message, focusTarget) {
        if (!validationAlert) return;
        validationAlert.textContent = message;
        validationAlert.style.display = '';
        validationAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        focusTarget?.focus();
    }

    function renderChips() {
        document.querySelectorAll('.ac-pl-chip').forEach(c => c.remove());
        custInputs.innerHTML = '';

        const ids = Object.keys(selected);
        custPlaceholder.style.display = ids.length ? 'none' : '';
        if (statCust) statCust.textContent = ids.length;
        if (customerCountBadge) {
            customerCountBadge.textContent = ids.length + ' ' + (ids.length === 1 ? 'عميل محدد' : 'عملاء محددون');
        }

        ids.forEach(id => {
            const chip = document.createElement('span');
            chip.className = 'ac-pl-chip';
            chip.innerHTML = `${selected[id]}<button type="button" data-id="${id}" class="ac-pl-chip__rm" aria-label="حذف">×</button>`;
            custChips.appendChild(chip);

            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'customer_ids[]';
            inp.value = id;
            custInputs.appendChild(inp);
        });
    }

    function filterDropdown(q) {
        let visible = 0;
        allOpts.forEach(opt => {
            const match = opt.dataset.name.toLowerCase().includes(q.toLowerCase());
            const alreadySelected = !!selected[opt.dataset.id];
            opt.style.display = (match && !alreadySelected) ? '' : 'none';
            if (match && !alreadySelected) visible++;
        });
        custEmpty.style.display = visible === 0 ? '' : 'none';
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            setRow(cb, cb.checked);
            updateStats();
            updateGovFilter();
            clearValidationState();
        });

        if (cb.checked) {
            setRow(cb, true);
        }
    });

    govRows.forEach(row => {
        row.querySelectorAll('.ac-pl-price-inp').forEach(input => {
            input.addEventListener('input', () => {
                if (input.classList.contains('ac-pl-delivery-inp')) {
                    const isSelected = row.querySelector('.ac-pl-gov-checkbox')?.checked;
                    row.classList.toggle('ac-pl-row--missing', !!isSelected && input.value.trim() === '');
                }
                updateStats();
                clearValidationState();
            });
        });
    });

    selectAll?.addEventListener('click', () => {
        checkboxes.forEach(cb => {
            cb.checked = true;
            setRow(cb, true);
        });
        updateStats();
        updateGovFilter();
    });

    clearAll?.addEventListener('click', () => {
        checkboxes.forEach(cb => {
            cb.checked = false;
            setRow(cb, false);
        });
        updateStats();
        updateGovFilter();
    });

    applyBtn?.addEventListener('click', () => {
        const del = bulkDelInp.value;
        const ret = bulkRetInp.value;

        checkboxes.forEach(cb => {
            if (!cb.checked) return;
            const row = document.getElementById('gov-row-' + cb.dataset.govId);
            if (!row) return;
            if (del !== '') row.querySelector('.ac-pl-delivery-inp').value = del;
            if (ret !== '') row.querySelector('.ac-pl-return-inp').value = ret;
        });

        updateStats();
        clearValidationState();
    });

    govSearch?.addEventListener('input', updateGovFilter);
    selectedOnlyToggle?.addEventListener('change', updateGovFilter);

    nameInput?.addEventListener('input', () => {
        updateSummaryName();
        updateReadiness();
        clearValidationState();
    });

    isActiveInput?.addEventListener('change', updateStatusSummary);

    custSearch?.addEventListener('focus', () => {
        custDropdown.style.display = '';
        filterDropdown(custSearch.value);
    });

    custSearch?.addEventListener('input', () => filterDropdown(custSearch.value));

    document.addEventListener('click', e => {
        if (!e.target.closest('#cust-box')) {
            custDropdown.style.display = 'none';
        }
    });

    custDropdown?.addEventListener('click', e => {
        const opt = e.target.closest('.ac-pl-cust-opt');
        if (!opt) return;
        selected[opt.dataset.id] = opt.dataset.name;
        custSearch.value = '';
        filterDropdown('');
        renderChips();
        custDropdown.style.display = 'none';
    });

    custChips?.addEventListener('click', e => {
        const btn = e.target.closest('.ac-pl-chip__rm');
        if (!btn) return;
        delete selected[btn.dataset.id];
        renderChips();
        filterDropdown(custSearch.value);
    });

    form?.addEventListener('submit', e => {
        clearValidationState();

        const formName = nameInput?.value.trim() || '';
        if (!formName) {
            e.preventDefault();
            showValidation('أدخل اسمًا واضحًا لقائمة الأسعار قبل الحفظ.', nameInput);
            return;
        }

        const selectedRows = getSelectedRows();
        if (!selectedRows.length) {
            e.preventDefault();
            showValidation('اختر محافظة واحدة على الأقل ثم أدخل سعر التسليم الخاص بها.', govSearch);
            return;
        }

        const missingPriceRow = selectedRows.find(row => {
            const input = row.querySelector('.ac-pl-delivery-inp');
            return !input || input.value.trim() === '';
        });

        if (missingPriceRow) {
            e.preventDefault();
            missingPriceRow.classList.add('ac-pl-row--missing');
            showValidation('يوجد محافظة محددة بدون سعر تسليم. أكمل الأسعار المطلوبة ثم احفظ القائمة.', missingPriceRow.querySelector('.ac-pl-delivery-inp'));
        }
    });

    renderChips();
    updateSummaryName();
    updateStatusSummary();
    updateStats();
    updateGovFilter();
})();
</script>
@endpush

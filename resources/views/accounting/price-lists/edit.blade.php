@extends('accounting._layout')

@section('title', 'تعديل: ' . $priceList->name)

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">تعديل قائمة الأسعار</h1>
    <div style="display:flex;align-items:center;gap:.45rem;flex-wrap:wrap">
        @if(companyModuleEnabled('customer_shipments'))
        <a href="{{ route('accounting.customer-shipments.index') }}" class="ac-btn ac-btn--ghost">شحنات العملاء</a>
        @endif
        <a href="{{ route('accounting.price-lists.show', $priceList) }}" class="ac-btn ac-btn--secondary">رجوع</a>
    </div>
</div>

<form method="POST" action="{{ route('accounting.price-lists.update', $priceList) }}" id="pl-form">
    @csrf
    @method('PUT')

    <div class="ac-pl-layout">

        {{-- ── Main ───────────────────────────────────────────────────────── --}}
        <div class="ac-pl-main">

            {{-- معلومات أساسية --}}
            <div class="ac-card ac-card--compact">
                <div class="ac-card__body">
                    <p class="ac-section-label">معلومات القائمة</p>
                    <div class="ac-pl-info-row">
                        <div class="ac-form-group" style="flex:1">
                            <label class="ac-label ac-label--required" for="name">اسم القائمة</label>
                            <input id="name" name="name" type="text"
                                   class="ac-control {{ $errors->has('name') ? 'ac-control--error' : '' }}"
                                   value="{{ old('name', $priceList->name) }}"
                                   placeholder="مثال: تعريفة القاهرة الكبرى..."
                                   required>
                            @error('name') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ac-form-group" style="flex:1">
                            <label class="ac-label" for="description">ملاحظات</label>
                            <input id="description" name="description" type="text"
                                   class="ac-control"
                                   placeholder="أي تفاصيل إضافية..."
                                   value="{{ old('description', $priceList->description) }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Governorates Table ──────────────────────────────────────── --}}
            <div class="ac-card ac-card--compact">
                <div class="ac-card__body">

                    <div class="ac-pl-tbl-header">
                        <p class="ac-section-label" style="margin:0">المحافظات والأسعار</p>
                        <div class="ac-pl-tbl-header__actions">
                            <button type="button" class="ac-btn ac-btn--ghost ac-btn--xs" id="select-all-btn">تحديد الكل</button>
                            <button type="button" class="ac-btn ac-btn--ghost ac-btn--xs" id="clear-all-btn">إلغاء الكل</button>
                            <a href="{{ route('accounting.governorates.index') }}"
                               class="ac-btn ac-btn--ghost ac-btn--xs" target="_blank">+ إضافة محافظة</a>
                        </div>
                    </div>

                    {{-- Bulk Bar --}}
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
                    </div>

                    {{-- Table --}}
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
                            <tbody>
                                @php
                                    $itemsByGov = $priceList->items->keyBy('governorate_id');
                                @endphp
                                @foreach($governorates as $gov)
                                    @php
                                        $existingItem = $itemsByGov->get($gov->id);
                                        $isEnabled    = old("govs.{$gov->id}.enabled", $existingItem ? '1' : null);
                                        $delPrice     = old("govs.{$gov->id}.price", $existingItem?->price ?? '');
                                        $retPrice     = old("govs.{$gov->id}.return_price", $existingItem?->return_price ?? '');
                                    @endphp
                                <tr class="ac-pl-row {{ $isEnabled ? 'ac-pl-row--active' : '' }}"
                                    id="gov-row-{{ $gov->id }}" data-gov-id="{{ $gov->id }}">
                                    <td class="ac-pl-tbl__check-col">
                                        <input type="checkbox"
                                               name="govs[{{ $gov->id }}][enabled]"
                                               value="1"
                                               class="ac-pl-gov-checkbox ac-checkbox"
                                               data-gov-id="{{ $gov->id }}"
                                               {{ $isEnabled ? 'checked' : '' }}>
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
                                                   value="{{ $delPrice }}"
                                                   {{ !$isEnabled ? 'disabled' : '' }}>
                                            <span class="ac-pl-two-prices__sep"></span>
                                            <input type="number"
                                                   name="govs[{{ $gov->id }}][return_price]"
                                                   class="ac-control ac-control--num ac-pl-return-inp"
                                                   step="0.01" min="0"
                                                   placeholder="—"
                                                   data-gov-id="{{ $gov->id }}"
                                                   value="{{ $retPrice }}"
                                                   {{ !$isEnabled ? 'disabled' : '' }}>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            {{-- ── العملاء ─────────────────────────────────────────────────── --}}
            <div class="ac-card ac-card--compact">
                <div class="ac-card__body">
                    <p class="ac-section-label">ربط العملاء بالقائمة</p>
                    <p style="font-size:.82rem;color:var(--ac-text-muted);margin:.25rem 0 .85rem">
                        اختر العملاء الذين سيطبّق عليهم هذا التسعير تلقائياً.
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

        </div>{{-- .ac-pl-main --}}

        {{-- ── Side ───────────────────────────────────────────────────────── --}}
        <div class="ac-pl-side">
            <div class="ac-card">
                <div class="ac-card__body">
                    <p class="ac-section-label">الإعدادات</p>

                    <div class="ac-form-group">
                        <label class="ac-prod-toggle-label">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   id="is_active" class="ac-prod-toggle-input"
                                   {{ old('is_active', $priceList->is_active ? '1' : '0') == '1' ? 'checked' : '' }}>
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
                            <span class="ac-pl-side-stat__label">عملاء مرتبطون</span>
                            <span class="ac-pl-side-stat__val" id="stat-cust-count">0</span>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:.6rem;margin-top:1.25rem">
                        <button type="submit" class="ac-btn ac-btn--primary ac-btn--full">
                            حفظ التعديلات
                        </button>
                        <a href="{{ route('accounting.price-lists.show', $priceList) }}"
                           class="ac-btn ac-btn--secondary ac-btn--full">إلغاء</a>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- .ac-pl-layout --}}
</form>

@push('scripts')
<script>
(function () {
    /* ── Governorates ───────────────────────────────────────────────────── */
    const checkboxes = document.querySelectorAll('.ac-pl-gov-checkbox');
    const bulkBar    = document.getElementById('bulk-bar');
    const bulkCount  = document.getElementById('bulk-count');
    const bulkDelInp = document.getElementById('bulk-price-del');
    const bulkRetInp = document.getElementById('bulk-price-ret');
    const applyBtn   = document.getElementById('apply-bulk-btn');
    const selectAll  = document.getElementById('select-all-btn');
    const clearAll   = document.getElementById('clear-all-btn');
    const statGov    = document.getElementById('stat-gov-count');

    function updateBulkBar() {
        const n = Array.from(checkboxes).filter(c => c.checked).length;
        if (statGov) statGov.textContent = n;
        bulkBar.style.display = n > 0 ? 'flex' : 'none';
        if (n > 0) bulkCount.textContent = n + ' ' + (n === 1 ? 'محافظة' : 'محافظات');
    }

    function setRow(cb, checked) {
        const row    = document.getElementById(`gov-row-${cb.dataset.govId}`);
        const delInp = row?.querySelector('.ac-pl-delivery-inp');
        const retInp = row?.querySelector('.ac-pl-return-inp');
        if (delInp) delInp.disabled = !checked;
        if (retInp) retInp.disabled = !checked;
        row?.classList.toggle('ac-pl-row--active', checked);
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', () => { setRow(cb, cb.checked); updateBulkBar(); });
        if (cb.checked) setRow(cb, true);
    });

    selectAll.addEventListener('click', () => { checkboxes.forEach(cb => { cb.checked = true;  setRow(cb, true);  }); updateBulkBar(); });
    clearAll .addEventListener('click', () => { checkboxes.forEach(cb => { cb.checked = false; setRow(cb, false); }); updateBulkBar(); });

    applyBtn.addEventListener('click', () => {
        const del = bulkDelInp.value, ret = bulkRetInp.value;
        checkboxes.forEach(cb => {
            if (!cb.checked) return;
            const row = document.getElementById(`gov-row-${cb.dataset.govId}`);
            if (del !== '') row?.querySelector('.ac-pl-delivery-inp') && (row.querySelector('.ac-pl-delivery-inp').value = del);
            if (ret !== '') row?.querySelector('.ac-pl-return-inp')   && (row.querySelector('.ac-pl-return-inp').value   = ret);
        });
    });

    updateBulkBar();

    /* ── Customers ──────────────────────────────────────────────────────── */
    const custSearch         = document.getElementById('cust-search');
    const custDropdown       = document.getElementById('cust-dropdown');
    const custChips          = document.getElementById('cust-chips');
    const custInputs         = document.getElementById('cust-inputs');
    const custPlaceholder    = document.getElementById('cust-placeholder');
    const custEmpty          = document.getElementById('cust-empty');
    const statCust           = document.getElementById('stat-cust-count');
    const customerCountBadge = document.getElementById('customer-count-badge');
    const allOpts            = Array.from(document.querySelectorAll('.ac-pl-cust-opt'));

    // Pre-populate from server (linked customers)
    const preSelected = @json($linkedCustomers ?? []);
    let selected = {};
    allOpts.forEach(opt => {
        if (preSelected.includes(parseInt(opt.dataset.id))) {
            selected[opt.dataset.id] = opt.dataset.name;
        }
    });

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
            inp.type = 'hidden'; inp.name = 'customer_ids[]'; inp.value = id;
            custInputs.appendChild(inp);
        });
    }

    function filterDropdown(q) {
        let visible = 0;
        allOpts.forEach(opt => {
            const match = opt.dataset.name.toLowerCase().includes(q.toLowerCase());
            const skip  = !!selected[opt.dataset.id];
            opt.style.display = (match && !skip) ? '' : 'none';
            if (match && !skip) visible++;
        });
        custEmpty.style.display = visible === 0 ? '' : 'none';
    }

    custSearch.addEventListener('focus', () => { custDropdown.style.display = ''; filterDropdown(custSearch.value); });
    custSearch.addEventListener('input', () => filterDropdown(custSearch.value));
    document.addEventListener('click', e => { if (!e.target.closest('#cust-box')) custDropdown.style.display = 'none'; });

    custDropdown.addEventListener('click', e => {
        const opt = e.target.closest('.ac-pl-cust-opt');
        if (!opt) return;
        selected[opt.dataset.id] = opt.dataset.name;
        custSearch.value = '';
        filterDropdown('');
        renderChips();
        custDropdown.style.display = 'none';
    });

    custChips.addEventListener('click', e => {
        const btn = e.target.closest('.ac-pl-chip__rm');
        if (!btn) return;
        delete selected[btn.dataset.id];
        renderChips();
        filterDropdown(custSearch.value);
    });

    renderChips();
})();
</script>
@endpush

@endsection

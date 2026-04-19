@extends('accounting._layout')

@section('title', 'فاتورة جديدة')

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">إنشاء فاتورة جديدة</h1>
    <a href="{{ route('accounting.invoices.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>

@if($errors->has('invoice'))
    <div class="ac-alert ac-alert--error">{{ $errors->first('invoice') }}</div>
@endif

@php
    $companySetting = app(\App\Modules\Accounting\Services\CompanySettingsService::class)
        ->forCompany(auth()->user()->company_id);
    $taxEnabled     = $companySetting->taxEnabled();
    $defaultTaxRate = $companySetting->taxRate();
    $taxName        = $companySetting->taxName();
@endphp
<form method="POST" action="{{ route('accounting.invoices.store') }}" id="inv-form">
    @csrf

    <div class="ac-inv-create-grid">

        {{-- ── Right column: Header info ─────────────────────────────────── --}}
        <div class="ac-inv-create-main">

            {{-- رقم الفاتورة (read-only preview) --}}
            <div class="ac-card ac-card--compact">
                <div class="ac-card__body">
                    <div class="ac-inv-number-preview">
                        <span class="ac-inv-number-preview__label">رقم الفاتورة</span>
                        <span class="ac-inv-number-preview__value">{{ $nextNumber }}</span>
                    </div>
                </div>
            </div>

            {{-- العميل والتاريخ --}}
            <div class="ac-card ac-card--compact">
                <div class="ac-card__body">
                    <div class="ac-form-row">
                        <div class="ac-form-group">
                            <label class="ac-label ac-label--required" for="customer_id">العميل</label>
                            <select id="customer_id" name="customer_id"
                                    class="ac-select {{ $errors->has('customer_id') ? 'ac-select--error' : '' }}"
                                    required>
                                <option value="">— اختر العميل —</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}"
                                        {{ (old('customer_id', $preselectedCustomer) == $c->id) ? 'selected' : '' }}>
                                        {{ $c->name }}
                                        @if($c->phone) ({{ $c->phone }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="ac-form-group">
                            <label class="ac-label ac-label--required" for="issue_date">تاريخ الإصدار</label>
                            <input id="issue_date" name="issue_date" type="date"
                                   class="ac-control {{ $errors->has('issue_date') ? 'ac-control--error' : '' }}"
                                   value="{{ old('issue_date', today()->toDateString()) }}" required>
                            @error('issue_date') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="ac-form-group">
                            <label class="ac-label" for="due_date">تاريخ الاستحقاق</label>
                            <input id="due_date" name="due_date" type="date"
                                   class="ac-control {{ $errors->has('due_date') ? 'ac-control--error' : '' }}"
                                   value="{{ old('due_date') }}">
                            @error('due_date') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="ac-form-group">
                            <label class="ac-label" for="payment_method">طريقة الدفع</label>
                            <select id="payment_method" name="payment_method" class="ac-select">
                                <option value="">— اختياري —</option>
                                <option value="cash"          {{ old('payment_method') === 'cash'          ? 'selected' : '' }}>نقداً</option>
                                <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>تحويل بنكي</option>
                                <option value="cheque"        {{ old('payment_method') === 'cheque'        ? 'selected' : '' }}>شيك</option>
                                <option value="card"          {{ old('payment_method') === 'card'          ? 'selected' : '' }}>بطاقة ائتمان</option>
                                <option value="other"         {{ old('payment_method') === 'other'         ? 'selected' : '' }}>أخرى</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── بنود الفاتورة ──────────────────────────────────────────── --}}
            <div class="ac-card ac-card--compact">
                <div class="ac-card__body">
                    <p class="ac-section-label">بنود الفاتورة</p>

                    @error('items') <div class="ac-alert ac-alert--error">{{ $message }}</div> @enderror

                    <div class="ac-inv-items" id="inv-items">
                        <div class="ac-inv-items__head">
                            <span>الوصف</span>
                            <span>الكمية</span>
                            <span>سعر الوحدة</span>
                            <span>الإجمالي</span>
                            <span></span>
                        </div>

                        {{-- صف افتراضي واحد --}}
                        <div class="ac-inv-item-row" data-row="0">
                            <input type="hidden" name="items[0][product_id]" class="ac-inv-item__product-id" value="{{ old('items.0.product_id') }}">
                            <div class="ac-inv-item__desc-wrap">
                                <input type="text" name="items[0][description]"
                                       class="ac-control ac-inv-item__desc"
                                       placeholder="وصف الخدمة أو المنتج"
                                       value="{{ old('items.0.description') }}" required>
                                <button type="button"
                                        class="ac-inv-item__catalog-btn ac-btn ac-btn--ghost ac-btn--xs"
                                        title="اختر من الكتالوج"
                                        onclick="openCatalog(this, 'sale')">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                                    </svg>
                                    كتالوج
                                </button>
                            </div>
                            <input type="number" name="items[0][quantity]" step="0.001" min="0.001"
                                   class="ac-control ac-control--num ac-inv-item__qty"
                                   placeholder="1" value="{{ old('items.0.quantity', 1) }}" required>
                            <input type="number" name="items[0][unit_price]" step="0.01" min="0"
                                   class="ac-control ac-control--num ac-inv-item__price"
                                   placeholder="0.00" value="{{ old('items.0.unit_price') }}" required>
                            <span class="ac-inv-item__total" data-total="0">0.00</span>
                            <button type="button" class="ac-inv-item__remove ac-btn ac-btn--ghost ac-btn--sm"
                                    title="حذف البند" disabled>✕</button>
                        </div>
                    </div>

                    <button type="button" id="add-item-btn" class="ac-btn ac-btn--secondary ac-btn--sm ac-mt-2">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        إضافة بند
                    </button>
                </div>
            </div>

            {{-- الملاحظات --}}
            <div class="ac-card ac-card--compact">
                <div class="ac-card__body">
                    <div class="ac-form-group">
                        <label class="ac-label" for="notes">ملاحظات</label>
                        <textarea id="notes" name="notes" rows="3" class="ac-control"
                                  placeholder="شروط الدفع، ملاحظات خاصة...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Left column: Totals + Submit ──────────────────────────────── --}}
        <div class="ac-inv-create-side">
            <div class="ac-card">
                <div class="ac-card__body">
                    <p class="ac-section-label">الملخص</p>
                    <div class="ac-inv-summary">
                        <div class="ac-inv-summary__row">
                            <span>المجموع قبل الضريبة</span>
                            <span id="inv-subtotal">0.00</span>
                        </div>
                        @if($taxEnabled)
                        <div class="ac-inv-summary__row" id="tax-summary-row">
                            <span>
                                {{ $taxName }}
                                (<input type="number" id="tax_rate_input" name="tax_rate"
                                        step="0.01" min="0" max="100"
                                        value="{{ old('tax_rate', $defaultTaxRate) }}"
                                        style="width:52px;text-align:center;"
                                        class="ac-control ac-control--num ac-control--inline">%)
                            </span>
                            <span id="inv-tax">0.00</span>
                        </div>
                        @else
                        {{-- Tax disabled — send 0 so InvoiceService knows not to apply it --}}
                        <input type="hidden" name="tax_rate" value="0">
                        @endif
                        <div class="ac-inv-summary__row ac-inv-summary__row--total">
                            <span>الإجمالي شامل الضريبة</span>
                            <span id="inv-grand-total">0.00</span>
                        </div>
                    </div>

                    <div class="ac-inv-create-actions">
                        <button type="submit" class="ac-btn ac-btn--primary ac-btn--full">
                            إنشاء الفاتورة
                        </button>
                        <a href="{{ route('accounting.invoices.index') }}"
                           class="ac-btn ac-btn--secondary ac-btn--full">إلغاء</a>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- .ac-inv-create-grid --}}

</form>

@endsection

{{-- ── Catalog Modal ────────────────────────────────────────────────────── --}}
<div id="catalog-modal" class="ac-catalog-modal" style="display:none" role="dialog" aria-modal="true">
    <div class="ac-catalog-modal__backdrop" onclick="closeCatalog()"></div>
    <div class="ac-catalog-modal__inner">
        <div class="ac-catalog-modal__header">
            <span class="ac-catalog-modal__title">اختر من الكتالوج</span>
            <button type="button" class="ac-catalog-modal__close" onclick="closeCatalog()">✕</button>
        </div>
        <div class="ac-catalog-modal__search">
            <input type="text" id="catalog-search-inp"
                   class="ac-control"
                   placeholder="ابحث بالاسم أو الرمز..."
                   autocomplete="off">
            <div class="ac-catalog-modal__type-tabs">
                <button type="button" class="ac-catalog-tab ac-catalog-tab--active" data-type="">الكل</button>
                <button type="button" class="ac-catalog-tab" data-type="service">خدمات</button>
                <button type="button" class="ac-catalog-tab" data-type="product">منتجات</button>
            </div>
        </div>
        <div id="catalog-results" class="ac-catalog-results">
            <div class="ac-catalog-empty">ابدأ بالكتابة للبحث…</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/* ── Invoice line items + tax JS ────────────────────────────────────────── */
(function () {
    const container   = document.getElementById('inv-items');
    const addBtn      = document.getElementById('add-item-btn');
    const subtotalEl  = document.getElementById('inv-subtotal');
    const taxEl       = document.getElementById('inv-tax');       // may be null
    const grandEl     = document.getElementById('inv-grand-total');
    const taxRateInp  = document.getElementById('tax_rate_input'); // may be null
    let   rowCount    = 1;

    function calcRow(row) {
        const qty   = parseFloat(row.querySelector('.ac-inv-item__qty').value)   || 0;
        const price = parseFloat(row.querySelector('.ac-inv-item__price').value) || 0;
        const total = Math.round(qty * price * 100) / 100;
        row.querySelector('.ac-inv-item__total').textContent = total.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        row.querySelector('.ac-inv-item__total').dataset.total = total;
        return total;
    }

    function recalcAll() {
        const rows     = container.querySelectorAll('.ac-inv-item-row');
        const subtotal = Array.from(rows).reduce((s, r) => s + calcRow(r), 0);
        const taxRate  = taxRateInp ? (parseFloat(taxRateInp.value) || 0) : 0;
        const tax      = Math.round(subtotal * taxRate) / 100;
        const grand    = Math.round((subtotal + tax) * 100) / 100;

        const fmt = (n) => n.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        if (subtotalEl) subtotalEl.textContent = fmt(subtotal);
        if (taxEl)      taxEl.textContent      = fmt(tax);
        grandEl.textContent = fmt(grand);

        rows.forEach(r => {
            r.querySelector('.ac-inv-item__remove').disabled = rows.length <= 1;
        });
    }

    if (taxRateInp) taxRateInp.addEventListener('input', recalcAll);

    function rowTemplate(idx) {
        return `<input type="hidden" name="items[${idx}][product_id]" class="ac-inv-item__product-id" value="">
        <div class="ac-inv-item__desc-wrap">
            <input type="text" name="items[${idx}][description]" class="ac-control ac-inv-item__desc" placeholder="وصف الخدمة أو المنتج" required>
            <button type="button" class="ac-inv-item__catalog-btn ac-btn ac-btn--ghost ac-btn--xs" title="اختر من الكتالوج" onclick="openCatalog(this, 'sale')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                كتالوج
            </button>
        </div>
        <input type="number" name="items[${idx}][quantity]"   class="ac-control ac-control--num ac-inv-item__qty"   step="0.001" min="0.001" placeholder="1"    value="1" required>
        <input type="number" name="items[${idx}][unit_price]" class="ac-control ac-control--num ac-inv-item__price" step="0.01"  min="0"     placeholder="0.00"          required>
        <span class="ac-inv-item__total" data-total="0">0.00</span>
        <button type="button" class="ac-inv-item__remove ac-btn ac-btn--ghost ac-btn--sm" title="حذف البند">✕</button>`;
    }

    function addRow() {
        const idx  = rowCount++;
        const div  = document.createElement('div');
        div.className = 'ac-inv-item-row';
        div.dataset.row = idx;
        div.innerHTML = rowTemplate(idx);
        container.appendChild(div);
        recalcAll();
        div.querySelector('.ac-inv-item__desc').focus();
    }

    function removeRow(btn) {
        btn.closest('.ac-inv-item-row').remove();
        recalcAll();
    }

    container.addEventListener('input',  (e) => { if (e.target.matches('.ac-inv-item__qty, .ac-inv-item__price')) recalcAll(); });
    container.addEventListener('click',  (e) => { if (e.target.matches('.ac-inv-item__remove')) removeRow(e.target); });
    addBtn.addEventListener('click', addRow);

    recalcAll();

    // expose recalcAll for catalog usage
    window._invRecalcAll = recalcAll;
})();

/* ── Catalog Modal ───────────────────────────────────────────────────────── */
(function () {
    const modal       = document.getElementById('catalog-modal');
    const searchInp   = document.getElementById('catalog-search-inp');
    const resultsEl   = document.getElementById('catalog-results');
    const searchUrl   = '{{ route('accounting.products.search') }}';
    const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;

    let activeBtn     = null;   // the catalog button that opened the modal
    let priceField    = 'sale'; // 'sale' for invoices
    let searchTimer   = null;
    let activeType    = '';

    window.openCatalog = function(btn, field) {
        activeBtn   = btn;
        priceField  = field;
        modal.style.display = 'flex';
        searchInp.value = '';
        setActiveType('');
        doSearch('');
        setTimeout(() => searchInp.focus(), 50);
    };

    window.closeCatalog = function() {
        modal.style.display = 'none';
        activeBtn = null;
    };

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display !== 'none') closeCatalog();
    });

    // Type tabs
    document.querySelectorAll('.ac-catalog-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            setActiveType(tab.dataset.type);
            doSearch(searchInp.value);
        });
    });

    function setActiveType(type) {
        activeType = type;
        document.querySelectorAll('.ac-catalog-tab').forEach(t => {
            t.classList.toggle('ac-catalog-tab--active', t.dataset.type === type);
        });
    }

    // Search input debounce
    searchInp.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => doSearch(searchInp.value), 250);
    });

    function doSearch(q) {
        resultsEl.innerHTML = '<div class="ac-catalog-empty">جارٍ البحث…</div>';
        const params = new URLSearchParams({ q });
        if (activeType) params.set('type', activeType);

        fetch(`${searchUrl}?${params}`, {
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(renderResults)
        .catch(() => {
            resultsEl.innerHTML = '<div class="ac-catalog-empty">حدث خطأ أثناء البحث.</div>';
        });
    }

    function renderResults(products) {
        if (!products.length) {
            resultsEl.innerHTML = '<div class="ac-catalog-empty">لا توجد نتائج.</div>';
            return;
        }
        resultsEl.innerHTML = products.map(p => {
            const price = priceField === 'sale'
                ? parseFloat(p.sale_price || 0)
                : parseFloat(p.purchase_price || p.sale_price || 0);
            const priceLabel = price.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
            const typeClass  = p.type === 'product' ? 'ac-prod-type-badge--product' : 'ac-prod-type-badge--service';
            const typeLabel  = p.type === 'product' ? 'منتج' : 'خدمة';
            return `<div class="ac-catalog-item" data-id="${p.id}"
                         data-name="${escHtml(p.name)}"
                         data-desc="${escHtml(p.description || p.name)}"
                         data-price="${price}"
                         data-tax="${parseFloat(p.tax_rate||0)}">
                <div class="ac-catalog-item__info">
                    <span class="ac-prod-type-badge ${typeClass}">${typeLabel}</span>
                    <span class="ac-catalog-item__name">${escHtml(p.name)}</span>
                    ${p.code ? `<span class="ac-catalog-item__code">${escHtml(p.code)}</span>` : ''}
                    ${p.unit ? `<span class="ac-catalog-item__unit">${escHtml(p.unit)}</span>` : ''}
                </div>
                <div class="ac-catalog-item__price">${priceLabel}</div>
            </div>`;
        }).join('');

        resultsEl.querySelectorAll('.ac-catalog-item').forEach(el => {
            el.addEventListener('click', () => selectProduct(el));
        });
    }

    function selectProduct(el) {
        if (!activeBtn) return;
        const row       = activeBtn.closest('.ac-inv-item-row');
        const desc      = row.querySelector('.ac-inv-item__desc');
        const price     = row.querySelector('.ac-inv-item__price');
        const productId = row.querySelector('.ac-inv-item__product-id');
        const tax       = document.getElementById('tax_rate_input');

        desc.value  = el.dataset.desc;
        price.value = el.dataset.price;
        if (productId) productId.value = el.dataset.id;

        // Optionally update tax rate from product if not overridden
        if (tax) {
            const pTax = parseFloat(el.dataset.tax);
            if (pTax > 0) tax.value = pTax;
        }

        if (window._invRecalcAll) window._invRecalcAll();
        closeCatalog();
        desc.focus();
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }
})();
</script>
@endpush

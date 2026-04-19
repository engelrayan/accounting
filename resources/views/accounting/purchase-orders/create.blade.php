@extends('accounting._layout')

@section('title', 'أمر شراء جديد')

@section('content')
<div style="font-family:'Cairo',sans-serif;direction:rtl;">

    {{-- Topbar --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
        <a href="{{ route('accounting.purchase-orders.index') }}"
           style="display:inline-flex;align-items:center;gap:.4rem;color:#065f46;text-decoration:none;font-size:.92rem;font-weight:600;">
            &#8594; أوامر الشراء
        </a>
        <h1 style="margin:0;font-size:1.25rem;font-weight:700;color:#1e293b;">أمر شراء جديد</h1>
    </div>

    {{-- Gradient Header --}}
    <div style="background:linear-gradient(135deg,#065f46 0%,#059669 100%);border-radius:16px;padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <h2 style="margin:0;font-size:1.4rem;font-weight:800;">إنشاء أمر شراء جديد</h2>
        <p style="margin:.35rem 0 0;opacity:.85;font-size:.92rem;">أدخل بيانات الأمر وأضف البنود المطلوبة</p>
    </div>

    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.25rem;color:#b91c1c;font-size:.9rem;">
        <ul style="margin:0;padding-right:1.25rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('accounting.purchase-orders.store') }}" id="poForm">
        @csrf

        {{-- Section 1: 2-col grid --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;">

            {{-- Left: بيانات الأمر --}}
            <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:1.5rem;">
                <h3 style="margin:0 0 1.25rem;font-size:1rem;font-weight:700;color:#1e293b;border-bottom:2px solid #e2e8f0;padding-bottom:.65rem;">
                    بيانات الأمر
                </h3>

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:.85rem;font-weight:600;color:#374151;margin-bottom:.4rem;">
                        المورد <span style="color:#ef4444;">*</span>
                    </label>
                    <select name="vendor_id" required
                            style="width:100%;padding:.55rem .85rem;border:1px solid #d1d5db;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.9rem;color:#1e293b;background:#fff;box-sizing:border-box;">
                        <option value="">-- اختر المورد --</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ old('vendor_id')==$vendor->id?'selected':'' }}>
                                {{ $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem;margin-bottom:1rem;">
                    <div>
                        <label style="display:block;font-size:.85rem;font-weight:600;color:#374151;margin-bottom:.4rem;">
                            تاريخ الأمر <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="date" name="date" required
                               value="{{ old('date', date('Y-m-d')) }}"
                               style="width:100%;padding:.55rem .85rem;border:1px solid #d1d5db;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.9rem;color:#1e293b;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;font-size:.85rem;font-weight:600;color:#374151;margin-bottom:.4rem;">تاريخ الاستلام المتوقع</label>
                        <input type="date" name="expected_date"
                               value="{{ old('expected_date') }}"
                               style="width:100%;padding:.55rem .85rem;border:1px solid #d1d5db;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.9rem;color:#1e293b;box-sizing:border-box;">
                    </div>
                </div>

                <div>
                    <label style="display:block;font-size:.85rem;font-weight:600;color:#374151;margin-bottom:.4rem;">ملاحظات</label>
                    <textarea name="notes" rows="3"
                              style="width:100%;padding:.55rem .85rem;border:1px solid #d1d5db;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.9rem;color:#1e293b;resize:vertical;box-sizing:border-box;">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- Right: معلومات إضافية --}}
            <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:1.5rem;">
                <h3 style="margin:0 0 1.25rem;font-size:1rem;font-weight:700;color:#1e293b;border-bottom:2px solid #e2e8f0;padding-bottom:.65rem;">
                    معلومات إضافية
                </h3>

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:.85rem;font-weight:600;color:#374151;margin-bottom:.4rem;">مرجع خارجي (اختياري)</label>
                    <input type="text" name="reference"
                           value="{{ old('reference') }}"
                           placeholder="رقم طلب الشراء الداخلي..."
                           style="width:100%;padding:.55rem .85rem;border:1px solid #d1d5db;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.9rem;color:#1e293b;box-sizing:border-box;">
                </div>

                <div style="margin-bottom:1.25rem;">
                    <label style="display:block;font-size:.85rem;font-weight:600;color:#374151;margin-bottom:.4rem;">شروط الدفع</label>
                    <select name="payment_terms"
                            style="width:100%;padding:.55rem .85rem;border:1px solid #d1d5db;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.9rem;color:#1e293b;background:#fff;box-sizing:border-box;">
                        <option value="">-- اختر --</option>
                        <option value="cash"   {{ old('payment_terms')==='cash'  ?'selected':'' }}>نقدي</option>
                        <option value="net15"  {{ old('payment_terms')==='net15' ?'selected':'' }}>صافي 15 يوم</option>
                        <option value="net30"  {{ old('payment_terms')==='net30' ?'selected':'' }}>صافي 30 يوم</option>
                        <option value="net60"  {{ old('payment_terms')==='net60' ?'selected':'' }}>صافي 60 يوم</option>
                    </select>
                </div>

                {{-- Info box --}}
                <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;padding:.85rem 1rem;display:flex;align-items:flex-start;gap:.6rem;">
                    <span style="color:#059669;font-size:1.1rem;line-height:1.3;">ℹ</span>
                    <p style="margin:0;font-size:.83rem;color:#065f46;line-height:1.5;">
                        رقم الأمر سيُولَّد تلقائياً بصيغة <strong>PO-2026-XXXX</strong> عند الحفظ
                    </p>
                </div>
            </div>
        </div>

        {{-- Section 2: Items --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);margin-bottom:1.25rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;">
                <h3 style="margin:0;font-size:1rem;font-weight:700;color:#1e293b;">بنود أمر الشراء</h3>
                <button type="button" onclick="addRow()"
                        style="display:inline-flex;align-items:center;gap:.4rem;padding:.45rem 1rem;background:#059669;color:#fff;border:none;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.85rem;font-weight:600;cursor:pointer;">
                    + إضافة بند
                </button>
            </div>

            <div style="overflow-x:auto;padding:1.25rem 1.5rem;">
                <table style="width:100%;border-collapse:collapse;" id="itemsTable">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th style="padding:.6rem .75rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;width:40px;">#</th>
                            <th style="padding:.6rem .75rem;text-align:right;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;min-width:140px;">المنتج (اختياري)</th>
                            <th style="padding:.6rem .75rem;text-align:right;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;min-width:160px;">الوصف</th>
                            <th style="padding:.6rem .75rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;width:70px;">الكمية</th>
                            <th style="padding:.6rem .75rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;width:80px;">الوحدة</th>
                            <th style="padding:.6rem .75rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;width:90px;">السعر</th>
                            <th style="padding:.6rem .75rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;width:70px;">الضريبة%</th>
                            <th style="padding:.6rem .75rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;width:90px;">الإجمالي</th>
                            <th style="padding:.6rem .75rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;width:36px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsTbody">
                        {{-- Row 0 (pre-populated) --}}
                        <tr data-idx="0">
                            <td style="padding:.5rem .6rem;text-align:center;font-size:.85rem;color:#64748b;border-bottom:1px solid #f1f5f9;" class="row-num">1</td>
                            <td style="padding:.5rem .6rem;border-bottom:1px solid #f1f5f9;">
                                <select name="items[0][product_id]" class="product-select"
                                        style="width:100%;padding:.4rem .55rem;border:1px solid #d1d5db;border-radius:6px;font-family:'Cairo',sans-serif;font-size:.82rem;color:#1e293b;background:#fff;">
                                    <option value="">-- منتج --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}"
                                                data-price="{{ $product->purchase_price ?? $product->cost_price ?? 0 }}"
                                                data-tax="{{ $product->tax_rate ?? 15 }}"
                                                data-unit="{{ $product->unit ?? '' }}"
                                                data-name="{{ $product->name }}">
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="padding:.5rem .6rem;border-bottom:1px solid #f1f5f9;">
                                <input type="text" name="items[0][description]" class="desc"
                                       style="width:100%;padding:.4rem .55rem;border:1px solid #d1d5db;border-radius:6px;font-family:'Cairo',sans-serif;font-size:.82rem;color:#1e293b;box-sizing:border-box;"
                                       placeholder="وصف البند">
                            </td>
                            <td style="padding:.5rem .6rem;border-bottom:1px solid #f1f5f9;">
                                <input type="number" name="items[0][quantity]" class="qty" value="1" min="0.01" step="0.01"
                                       style="width:100%;padding:.4rem .55rem;border:1px solid #d1d5db;border-radius:6px;font-family:'Cairo',sans-serif;font-size:.82rem;text-align:center;box-sizing:border-box;">
                            </td>
                            <td style="padding:.5rem .6rem;border-bottom:1px solid #f1f5f9;">
                                <input type="text" name="items[0][unit]" class="unit"
                                       style="width:100%;padding:.4rem .55rem;border:1px solid #d1d5db;border-radius:6px;font-family:'Cairo',sans-serif;font-size:.82rem;text-align:center;box-sizing:border-box;"
                                       placeholder="وحدة">
                            </td>
                            <td style="padding:.5rem .6rem;border-bottom:1px solid #f1f5f9;">
                                <input type="number" name="items[0][unit_price]" class="price" value="0" min="0" step="0.01"
                                       style="width:100%;padding:.4rem .55rem;border:1px solid #d1d5db;border-radius:6px;font-family:'Cairo',sans-serif;font-size:.82rem;text-align:center;box-sizing:border-box;">
                            </td>
                            <td style="padding:.5rem .6rem;border-bottom:1px solid #f1f5f9;">
                                <input type="number" name="items[0][tax_rate]" class="taxr" value="15" min="0" max="100" step="0.01"
                                       style="width:100%;padding:.4rem .55rem;border:1px solid #d1d5db;border-radius:6px;font-family:'Cairo',sans-serif;font-size:.82rem;text-align:center;box-sizing:border-box;">
                            </td>
                            <td style="padding:.5rem .6rem;border-bottom:1px solid #f1f5f9;">
                                <input type="number" name="items[0][total]" class="rowtotal" value="0.00" readonly
                                       style="width:100%;padding:.4rem .55rem;border:1px solid #e2e8f0;border-radius:6px;font-family:'Cairo',sans-serif;font-size:.82rem;text-align:center;background:#f8fafc;box-sizing:border-box;">
                            </td>
                            <td style="padding:.5rem .6rem;text-align:center;border-bottom:1px solid #f1f5f9;">
                                <button type="button" onclick="deleteRow(this)"
                                        style="background:none;border:1px solid #fca5a5;color:#ef4444;border-radius:6px;width:26px;height:26px;cursor:pointer;font-size:.9rem;line-height:1;font-family:'Cairo',sans-serif;">
                                    ✕
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div style="display:flex;justify-content:flex-end;padding:1rem 1.5rem 1.5rem;">
                <div style="min-width:260px;">
                    <div style="display:flex;justify-content:space-between;padding:.4rem 0;font-size:.9rem;color:#475569;border-bottom:1px solid #f1f5f9;">
                        <span>المجموع الفرعي:</span>
                        <span><span id="sumSubtotal">0.00</span> ريال</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:.4rem 0;font-size:.9rem;color:#475569;border-bottom:1px solid #f1f5f9;">
                        <span>الضريبة:</span>
                        <span><span id="sumTax">0.00</span> ريال</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:.55rem 0;font-size:1.05rem;font-weight:800;color:#1e293b;">
                        <span>الإجمالي:</span>
                        <span><span id="sumTotal">0.00</span> ريال</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div style="display:flex;align-items:center;gap:.85rem;">
            <button type="submit"
                    style="padding:.6rem 1.75rem;background:#065f46;color:#fff;border:none;border-radius:9px;font-family:'Cairo',sans-serif;font-size:.95rem;font-weight:700;cursor:pointer;">
                حفظ كمسودة
            </button>
            <a href="{{ route('accounting.purchase-orders.index') }}"
               style="padding:.6rem 1.5rem;background:#fff;color:#64748b;border:1px solid #d1d5db;border-radius:9px;font-family:'Cairo',sans-serif;font-size:.95rem;font-weight:600;text-decoration:none;display:inline-block;">
                إلغاء
            </a>
        </div>

    </form>
</div>

{{-- Row template (hidden) --}}
<template id="rowTemplate">
    <tr data-idx="__IDX__">
        <td style="padding:.5rem .6rem;text-align:center;font-size:.85rem;color:#64748b;border-bottom:1px solid #f1f5f9;" class="row-num">__NUM__</td>
        <td style="padding:.5rem .6rem;border-bottom:1px solid #f1f5f9;">
            <select name="items[__IDX__][product_id]" class="product-select"
                    style="width:100%;padding:.4rem .55rem;border:1px solid #d1d5db;border-radius:6px;font-family:'Cairo',sans-serif;font-size:.82rem;color:#1e293b;background:#fff;">
                <option value="">-- منتج --</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}"
                            data-price="{{ $product->purchase_price ?? $product->cost_price ?? 0 }}"
                            data-tax="{{ $product->tax_rate ?? 15 }}"
                            data-unit="{{ $product->unit ?? '' }}"
                            data-name="{{ $product->name }}">
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td style="padding:.5rem .6rem;border-bottom:1px solid #f1f5f9;">
            <input type="text" name="items[__IDX__][description]" class="desc"
                   style="width:100%;padding:.4rem .55rem;border:1px solid #d1d5db;border-radius:6px;font-family:'Cairo',sans-serif;font-size:.82rem;color:#1e293b;box-sizing:border-box;"
                   placeholder="وصف البند">
        </td>
        <td style="padding:.5rem .6rem;border-bottom:1px solid #f1f5f9;">
            <input type="number" name="items[__IDX__][quantity]" class="qty" value="1" min="0.01" step="0.01"
                   style="width:100%;padding:.4rem .55rem;border:1px solid #d1d5db;border-radius:6px;font-family:'Cairo',sans-serif;font-size:.82rem;text-align:center;box-sizing:border-box;">
        </td>
        <td style="padding:.5rem .6rem;border-bottom:1px solid #f1f5f9;">
            <input type="text" name="items[__IDX__][unit]" class="unit"
                   style="width:100%;padding:.4rem .55rem;border:1px solid #d1d5db;border-radius:6px;font-family:'Cairo',sans-serif;font-size:.82rem;text-align:center;box-sizing:border-box;"
                   placeholder="وحدة">
        </td>
        <td style="padding:.5rem .6rem;border-bottom:1px solid #f1f5f9;">
            <input type="number" name="items[__IDX__][unit_price]" class="price" value="0" min="0" step="0.01"
                   style="width:100%;padding:.4rem .55rem;border:1px solid #d1d5db;border-radius:6px;font-family:'Cairo',sans-serif;font-size:.82rem;text-align:center;box-sizing:border-box;">
        </td>
        <td style="padding:.5rem .6rem;border-bottom:1px solid #f1f5f9;">
            <input type="number" name="items[__IDX__][tax_rate]" class="taxr" value="15" min="0" max="100" step="0.01"
                   style="width:100%;padding:.4rem .55rem;border:1px solid #d1d5db;border-radius:6px;font-family:'Cairo',sans-serif;font-size:.82rem;text-align:center;box-sizing:border-box;">
        </td>
        <td style="padding:.5rem .6rem;border-bottom:1px solid #f1f5f9;">
            <input type="number" name="items[__IDX__][total]" class="rowtotal" value="0.00" readonly
                   style="width:100%;padding:.4rem .55rem;border:1px solid #e2e8f0;border-radius:6px;font-family:'Cairo',sans-serif;font-size:.82rem;text-align:center;background:#f8fafc;box-sizing:border-box;">
        </td>
        <td style="padding:.5rem .6rem;text-align:center;border-bottom:1px solid #f1f5f9;">
            <button type="button" onclick="deleteRow(this)"
                    style="background:none;border:1px solid #fca5a5;color:#ef4444;border-radius:6px;width:26px;height:26px;cursor:pointer;font-size:.9rem;line-height:1;font-family:'Cairo',sans-serif;">
                ✕
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
let rowIdx = 0;

function addRow() {
    rowIdx++;
    const template = document.getElementById('rowTemplate');
    const html = template.innerHTML
        .replace(/__IDX__/g, rowIdx)
        .replace(/__NUM__/g, rowIdx + 1);
    const tbody = document.getElementById('itemsTbody');
    const tmpDiv = document.createElement('div');
    tmpDiv.innerHTML = html;
    const newRow = tmpDiv.querySelector('tr');
    tbody.appendChild(newRow);
    newRow.querySelectorAll('.qty,.price,.taxr').forEach(el => el.addEventListener('input', recalc));
    newRow.querySelector('.product-select').addEventListener('change', function() {
        handleProductChange(this);
    });
    recalc();
}

function deleteRow(btn) {
    btn.closest('tr').remove();
    recalc();
    renumberRows();
}

function renumberRows() {
    document.querySelectorAll('#itemsTbody tr').forEach((tr, i) => {
        const numCell = tr.querySelector('.row-num');
        if (numCell) numCell.textContent = i + 1;
    });
}

function recalc() {
    let subtotal = 0, tax = 0;
    document.querySelectorAll('#itemsTbody tr').forEach((tr, i) => {
        const numCell = tr.querySelector('.row-num');
        if (numCell) numCell.textContent = i + 1;
        const qty   = parseFloat(tr.querySelector('.qty').value)   || 0;
        const price = parseFloat(tr.querySelector('.price').value) || 0;
        const taxR  = parseFloat(tr.querySelector('.taxr').value)  || 0;
        const base  = qty * price;
        const t     = base * taxR / 100;
        const total = base + t;
        tr.querySelector('.rowtotal').value = total.toFixed(2);
        subtotal += base;
        tax      += t;
    });
    document.getElementById('sumSubtotal').textContent = subtotal.toFixed(2);
    document.getElementById('sumTax').textContent      = tax.toFixed(2);
    document.getElementById('sumTotal').textContent    = (subtotal + tax).toFixed(2);
}

function handleProductChange(sel) {
    const tr = sel.closest('tr');
    if (sel.value) {
        const opt = sel.options[sel.selectedIndex];
        tr.querySelector('.desc').value  = opt.dataset.name  || '';
        tr.querySelector('.price').value = opt.dataset.price || 0;
        tr.querySelector('.taxr').value  = opt.dataset.tax   || 0;
        tr.querySelector('.unit').value  = opt.dataset.unit  || '';
    }
    recalc();
}

document.querySelectorAll('#itemsTbody .product-select').forEach(sel => {
    sel.addEventListener('change', function() { handleProductChange(this); });
});
document.querySelectorAll('#itemsTbody .qty, #itemsTbody .price, #itemsTbody .taxr').forEach(el => {
    el.addEventListener('input', recalc);
});

recalc();
</script>
@endpush
